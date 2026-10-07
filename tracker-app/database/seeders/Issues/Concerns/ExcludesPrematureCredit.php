<?php

declare(strict_types=1);

namespace Database\Seeders\Issues\Concerns;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keeps backfill seeders from crediting a club for a shift that happened before the trooper
 * actually joined it.
 *
 * getEligibleCreditOrganizations() has no concept of when a trooper joined a club — fine for
 * live self-service confirmation (a trooper confirms shortly after the event), wrong for
 * backfilling old uncredited shifts with current membership. A trooper who joined a new club
 * recently would otherwise get every old uncredited shift backfilled to that club too.
 */
trait ExcludesPrematureCredit
{
    private const IMPORT_CUTOFF = '2026-05-30 00:00:00';

    /**
     * Earliest real join date per (trooper, root club). Every legacy trooper's membership rows
     * were created in one batch on the day of the Florida Garrison import regardless of when
     * they actually joined, so rows from that batch carry no usable date signal and are
     * excluded entirely rather than treated as "joined on import day."
     *
     * Just as importantly: routine membership-hierarchy maintenance (e.g. moving a trooper from
     * a root-level assignment to a specific region, or Fix242-style repairs) creates a brand new
     * row with a fresh created_at for a membership that already existed — a decades-old member
     * can get a row dated last month this way. A post-import row is only trusted as a real join
     * signal when the (trooper, root club) pair has *no* row at all, of any kind, from the
     * import batch — any prior presence there, active or not, means we can't tell when they
     * really joined, so no gate is applied.
     *
     * @param  Collection<int, Organization>  $all_orgs
     * @return array<int, array<int, Carbon>> [trooper_id][root_org_id] => earliest join date
     */
    private function buildJoinSignals(Collection $all_orgs): array
    {
        $import_era_pairs = $this->buildImportEraPresence($all_orgs);

        $signals = [];

        $organization_rows = DB::table('tt_trooper_organizations')
            ->where('created_at', '>=', self::IMPORT_CUTOFF)
            ->get(['trooper_id', 'organization_id', 'join_date', 'created_at']);

        $assignment_rows = DB::table('tt_trooper_assignments')
            ->where('is_member', true)
            ->where('created_at', '>=', self::IMPORT_CUTOFF)
            ->get(['trooper_id', 'organization_id', 'created_at']);

        foreach ($organization_rows as $row)
        {
            $this->recordJoinSignal($signals, $all_orgs, $import_era_pairs, $row->trooper_id, $row->organization_id, $row->join_date ?? $row->created_at);
        }

        foreach ($assignment_rows as $row)
        {
            $this->recordJoinSignal($signals, $all_orgs, $import_era_pairs, $row->trooper_id, $row->organization_id, $row->created_at);
        }

        return $signals;
    }

    /**
     * (trooper_id, root_org_id) pairs with real evidence of presence at that club during the
     * import batch. Presence here means "no reliable join date," not "joined on import day."
     *
     * The importer (TrooperOrganizationSeeder) creates a tt_trooper_assignments row for every
     * (trooper, org) combination unconditionally, defaulting is_member to false — so a plain
     * "any assignment row exists" check is meaningless, it's true for everyone regardless of
     * membership. Only an active (is_member = true) assignment counts. A tt_trooper_organizations
     * row, on the other hand, is only ever created when the importer found a real identifier —
     * never as boilerplate — so any row there counts as presence regardless of its current
     * status (even a later-retired one still means "we knew about this at import time").
     *
     * @param  Collection<int, Organization>  $all_orgs
     * @return array<string, true>  keyed "trooper_id:root_org_id"
     */
    private function buildImportEraPresence(Collection $all_orgs): array
    {
        $pairs = [];

        $rows = DB::table('tt_trooper_organizations')
            ->where('created_at', '<', self::IMPORT_CUTOFF)
            ->get(['trooper_id', 'organization_id'])
            ->merge(DB::table('tt_trooper_assignments')
                ->where('created_at', '<', self::IMPORT_CUTOFF)
                ->where('is_member', true)
                ->get(['trooper_id', 'organization_id']));

        foreach ($rows as $row)
        {
            $node_path = $all_orgs->get($row->organization_id)?->node_path;

            if ($node_path === null)
            {
                continue;
            }

            $root_id = Organization::rootIdFromPath($node_path);
            $pairs["{$row->trooper_id}:{$root_id}"] = true;
        }

        return $pairs;
    }

    /**
     * @param  array<int, array<int, Carbon>>  $signals
     * @param  array<string, true>  $import_era_pairs
     */
    private function recordJoinSignal(
        array &$signals,
        Collection $all_orgs,
        array $import_era_pairs,
        int $trooper_id,
        int $organization_id,
        string $date,
    ): void {
        $node_path = $all_orgs->get($organization_id)?->node_path;

        if ($node_path === null)
        {
            return;
        }

        $root_id = Organization::rootIdFromPath($node_path);

        if (isset($import_era_pairs["{$trooper_id}:{$root_id}"]))
        {
            // Had some presence at this club during the import — no reliable join date.
            return;
        }

        $joined_at = Carbon::parse($date);

        if (!isset($signals[$trooper_id][$root_id]) || $joined_at->lt($signals[$trooper_id][$root_id]))
        {
            $signals[$trooper_id][$root_id] = $joined_at;
        }
    }

    /**
     * Drops any org id whose root club has a join signal later than the shift date. No signal
     * for a (trooper, root) pair means no opinion — nothing is excluded on that basis.
     *
     * @param  array<int, int>  $org_ids
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<int, array<int, Carbon>>  $join_signals
     * @return array<int, int>
     */
    private function excludePremature(
        array $org_ids,
        int $trooper_id,
        Carbon $shift_date,
        Collection $all_orgs,
        array $join_signals,
    ): array {
        return collect($org_ids)
            ->reject(function (int $org_id) use ($trooper_id, $shift_date, $all_orgs, $join_signals) {
                $node_path = $all_orgs->get($org_id)?->node_path;

                if ($node_path === null)
                {
                    return false;
                }

                $root_id = Organization::rootIdFromPath($node_path);
                $joined_at = $join_signals[$trooper_id][$root_id] ?? null;

                return $joined_at !== null && $shift_date->lt($joined_at);
            })
            ->values()
            ->all();
    }
}
