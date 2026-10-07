<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Fix408AmbiguousMemberships;
use App\Mail\Fix408OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Database\Seeders\Issues\Concerns\ExcludesPrematureCredit;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Corrects the Florida Garrison import's false club-membership bug, and a second, unrelated
 * credit bug found while chasing it.
 *
 * Bug 1 — false membership: TrooperOrganizationSeeder used to grant club membership from a
 * non-empty legacy identity field (tkid, rebelforum, ...) alone, without checking the club's own
 * permission flag (p501, pRebel, ...). The old tracker's signup form required a TKID-style value
 * from everyone regardless of club, so plenty of troopers picked up an active membership for a
 * club they were never in. Once Fix242's orphaned-TrooperOrganization repair ran, some of those
 * also got a real TrooperAssignment, which then fed troop credit and club-scoped achievements for
 * years.
 *
 * Only corrects (trooper, club) pairs where the legacy permission flag is 0 and the current
 * tt_trooper_organizations row is already retired/reserve — someone already flagged it as wrong,
 * it just never propagated to the TrooperAssignment. Pairs still marked "active" are left alone
 * and reported to administrators, since the trooper may have joined for real since the import.
 *
 * Bug 2 — legacy signup ignored when backfilling credit: Fix406/407 backfill missing credit from
 * current/import-era club membership, never from the legacy event_sign_up/costumes.club tag that
 * recorded which specific club a trooper represented at that specific historical shift. For a
 * trooper who is (or was, at import) a member of more than one club, that backfill credits every
 * club they're in today rather than the one(s) the legacy tag actually claims — over-crediting a
 * dual member for shifts from before they were dual, or even crediting the wrong club outright for
 * a trooper who's since left the club the legacy tag names. correctLegacyMismatchedCredit() below
 * runs site-wide (not just against the false-membership troopers above) and treats the legacy tag
 * as authoritative whenever one exists, since it's the more specific, per-shift-accurate record.
 */
class Fix408 extends Seeder
{
    use ExcludesPrematureCredit;
    use HasClubMaps;
    use HasSquadMaps;

    public function run(MagicBus $bus): void
    {
        if (!Schema::hasTable('troopers'))
        {
            $this->command?->warn('Fix408 requires the legacy troopers table; nothing to do.');

            return;
        }

        $outstanding_credit_rows = [];
        $ambiguous_memberships = [];

        DB::transaction(function () use (&$outstanding_credit_rows, &$ambiguous_memberships): void
        {
            $counts = [
                'memberships_corrected' => 0,
                'memberships_ambiguous' => 0,
                'credit_rows_scanned' => 0,
                'corrected_partial_credit' => 0,
                'recovered_live' => 0,
                'recovered_legacy' => 0,
                'skipped_no_eligible_org' => 0,
                'achievements_removed' => 0,
                'legacy_mismatch_corrected' => 0,
                'legacy_club_ambiguous' => 0,
            ];

            $all_orgs = Organization::all([Organization::ID, Organization::NODE_PATH])->keyBy(Organization::ID);
            $join_signals = $this->buildJoinSignals($all_orgs);

            [$false_root_ids_by_trooper, $ambiguous_memberships] = $this->auditMemberships($counts, $all_orgs);
            $this->auditSquadMemberships($false_root_ids_by_trooper, $counts);

            $outstanding_credit_rows = $this->correctCredit($false_root_ids_by_trooper, $all_orgs, $join_signals, $counts);
            $this->removeFalseAchievements($false_root_ids_by_trooper, $counts);

            $this->correctLegacyMismatchedCredit($false_root_ids_by_trooper, $all_orgs, $counts);

            $this->printSummary($counts);
        });

        $this->emailOutstandingCredit($bus, $outstanding_credit_rows);
        $this->emailAmbiguousMemberships($bus, $ambiguous_memberships);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  Collection<int, Organization>  $all_orgs
     * @return array{0: array<int, array<int, int>>, 1: array<int, array<string, mixed>>}
     */
    private function auditMemberships(array &$counts, Collection $all_orgs): array
    {
        $club_map = $this->buildOrganizationClubMap();
        $false_root_ids_by_trooper = [];
        $ambiguous_memberships = [];

        if ($club_map->isEmpty())
        {
            return [$false_root_ids_by_trooper, $ambiguous_memberships];
        }

        $legacy_troopers = DB::table('troopers')->get();

        foreach ($legacy_troopers as $legacy_trooper)
        {
            foreach ($club_map as $club)
            {
                $mismatch = $this->findMembershipMismatch($legacy_trooper, $club);

                if ($mismatch === null)
                {
                    continue;
                }

                $is_safe_status = in_array(
                    $mismatch['membership_status'],
                    [MembershipStatus::RETIRED, MembershipStatus::RESERVE],
                    true,
                );

                if (!$is_safe_status)
                {
                    // active, pending, denied, etc. — something else is going on, let a human decide
                    $ambiguous_memberships[] = $this->buildAmbiguousReportRow($mismatch, $all_orgs);
                    $counts['memberships_ambiguous']++;

                    continue;
                }

                $this->correctMembership($mismatch['trooper_organization'], $mismatch['assignment']);
                $false_root_ids_by_trooper[$mismatch['trooper_id']][] = $mismatch['organization_id'];

                if (!$mismatch['already_corrected'])
                {
                    $counts['memberships_corrected']++;
                }
            }
        }

        return [$false_root_ids_by_trooper, $ambiguous_memberships];
    }

    /**
     * Same bug, one level down: assignUnit() grants a Florida Garrison squad membership from the
     * legacy `squad` field alone, also without checking p501. All of HasSquadMaps' squads are
     * 501st units, so a correction here counts toward the 501st root id, same as above.
     *
     * @param  array<int, array<int, int>>  $false_root_ids_by_trooper
     * @param  array<string, int>  $counts
     */
    private function auditSquadMemberships(array &$false_root_ids_by_trooper, array &$counts): void
    {
        $p501_org = Organization::firstWhere(Organization::NAME, '501st Legion');

        if ($p501_org === null)
        {
            return;
        }

        try
        {
            $squad_map = collect($this->getSquadMap());
        }
        catch (RuntimeException)
        {
            return;
        }

        $legacy_troopers = DB::table('troopers')->where('p501', 0)->get(['id', 'squad']);

        foreach ($legacy_troopers as $legacy_trooper)
        {
            $squad = $squad_map->get($legacy_trooper->squad);

            if ($squad === null)
            {
                continue;
            }

            $assignment = TrooperAssignment::query()
                ->where(TrooperAssignment::TROOPER_ID, $legacy_trooper->id)
                ->where(TrooperAssignment::ORGANIZATION_ID, $squad['id'])
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->whereNull(TrooperAssignment::DELETED_AT)
                ->first();

            if ($assignment === null)
            {
                continue;
            }

            $assignment->is_member = false;
            $assignment->save();

            if (!$assignment->is_moderator && !$assignment->should_notify)
            {
                $assignment->delete();
            }

            $false_root_ids_by_trooper[$legacy_trooper->id][] = $p501_org->id;
            $counts['memberships_corrected']++;
        }
    }

    /**
     * @param  array<string, mixed>  $mismatch
     * @param  Collection<int, Organization>  $all_orgs
     * @return array<string, mixed>
     */
    private function buildAmbiguousReportRow(array $mismatch, Collection $all_orgs): array
    {
        return [
            'trooper_id' => $mismatch['trooper_id'],
            'trooper_name' => $mismatch['trooper_name'],
            'organization_name' => $all_orgs->get($mismatch['organization_id'])?->name ?? 'Unknown',
            'membership_status' => $mismatch['membership_status']->value,
            'event_trooper_row_count' => $this->countCreditedRows($mismatch['trooper_id'], $mismatch['organization_id'], $all_orgs),
            'achievement_row_count' => TrooperAchievement::query()
                ->where(TrooperAchievement::TROOPER_ID, $mismatch['trooper_id'])
                ->where(TrooperAchievement::ORGANIZATION_ID, $mismatch['organization_id'])
                ->count(),
        ];
    }

    /** @param  Collection<int, Organization>  $all_orgs */
    private function countCreditedRows(int $trooper_id, int $root_org_id, Collection $all_orgs): int
    {
        return EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $trooper_id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->get([EventTrooper::COSTUME_ORGANIZATION_IDS])
            ->filter(function (EventTrooper $event_trooper) use ($root_org_id, $all_orgs)
            {
                foreach ($event_trooper->costume_organization_ids as $org_id)
                {
                    $node_path = $all_orgs->get($org_id)?->node_path;

                    if ($node_path !== null && Organization::rootIdFromPath($node_path) === $root_org_id)
                    {
                        return true;
                    }
                }

                return false;
            })
            ->count();
    }

    /** @return Collection<int, array{id: int, permission_column: string, identity: string}> */
    private function buildOrganizationClubMap(): Collection
    {
        try
        {
            return collect($this->getOrganizationClubMap())->values();
        }
        catch (Exception)
        {
            $this->command?->warn('Fix408: named clubs not found in this environment, skipping.');

            return collect();
        }
    }

    /**
     * Keys off the tt_trooper_organizations row itself, trashed or not, rather than whether a
     * TrooperAssignment is still active — so a re-run keeps including already-corrected troopers
     * in the credit/achievement passes (needed since resolveLegacyOrgIds can hand back the same
     * false credit it's supposed to replace) without re-counting or re-deleting the membership.
     *
     * @param  array{id: int, permission_column: string, identity: string}  $club
     * @return array<string, mixed>|null
     */
    private function findMembershipMismatch(object $legacy_trooper, array $club): ?array
    {
        $permission_flag = (int) $legacy_trooper->{$club['permission_column']};
        $identifier = $club['identity'] !== '' ? $legacy_trooper->{$club['identity']} : null;
        $has_identifier = $identifier !== null && $identifier !== '' && $identifier !== '0';

        if ($permission_flag !== 0 || !$has_identifier)
        {
            return null;
        }

        $trooper_org = TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $legacy_trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $club['id'])
            ->first();

        if ($trooper_org === null)
        {
            // Nothing lingering for this club — never created.
            return null;
        }

        $assignment = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $legacy_trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $club['id'])
            ->whereNull(TrooperAssignment::DELETED_AT)
            ->first();

        return [
            'trooper_id' => $legacy_trooper->id,
            'trooper_name' => $legacy_trooper->name,
            'organization_id' => $club['id'],
            'membership_status' => $trooper_org->membership_status,
            'trooper_organization' => $trooper_org,
            'already_corrected' => $trooper_org->trashed(),
            'assignment' => $assignment,
        ];
    }

    private function correctMembership(TrooperOrganization $trooper_org, ?TrooperAssignment $assignment): void
    {
        if (!$trooper_org->trashed())
        {
            $trooper_org->delete();
        }

        if ($assignment === null || !$assignment->is_member)
        {
            return;
        }

        $assignment->is_member = false;
        $assignment->save();

        if (!$assignment->is_moderator && !$assignment->should_notify)
        {
            $assignment->delete();
        }
    }

    /**
     * @param  array<int, array<int, int>>  $false_root_ids_by_trooper
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<int, array<int, Carbon>>  $join_signals
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function correctCredit(array $false_root_ids_by_trooper, Collection $all_orgs, array $join_signals, array &$counts): array
    {
        $outstanding_rows = [];

        if (empty($false_root_ids_by_trooper))
        {
            return $outstanding_rows;
        }

        $costume_club_map = $this->buildCostumeClubMap();

        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereIn(EventTrooper::TROOPER_ID, array_keys($false_root_ids_by_trooper))
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->with(['trooper.trooper_costumes.organization_costume', 'trooper.trooper_assignments', 'costume', 'event_shift.event'])
            ->chunkById(200, function ($event_troopers) use (
                &$outstanding_rows,
                &$counts,
                $false_root_ids_by_trooper,
                $all_orgs,
                $join_signals,
                $costume_club_map,
            ): void {
                foreach ($event_troopers as $event_trooper)
                {
                    $this->correctCreditForRow(
                        $event_trooper,
                        $false_root_ids_by_trooper[$event_trooper->trooper_id],
                        $all_orgs,
                        $join_signals,
                        $costume_club_map,
                        $counts,
                        $outstanding_rows,
                    );
                }
            });

        return $outstanding_rows;
    }

    /**
     * @param  array<int, int>  $false_root_ids
     * @param  Collection<int, object>  $all_orgs
     * @param  array<int, array<int, Carbon>>  $join_signals
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function correctCreditForRow(
        EventTrooper $event_trooper,
        array $false_root_ids,
        Collection $all_orgs,
        array $join_signals,
        Collection $costume_club_map,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $original_ids = $event_trooper->costume_organization_ids;

        $remaining_ids = collect($original_ids)
            ->reject(function (int $org_id) use ($false_root_ids, $all_orgs)
            {
                $node_path = $all_orgs->get($org_id)?->node_path;

                return $node_path !== null && in_array(Organization::rootIdFromPath($node_path), $false_root_ids, true);
            })
            ->values();

        if ($remaining_ids->count() === count($original_ids))
        {
            // Nothing in this row was credited to the false club — not our row to touch.
            return;
        }

        $counts['credit_rows_scanned']++;

        $this->reresolveCredit($event_trooper, $remaining_ids->all(), $false_root_ids, $all_orgs, $join_signals, $costume_club_map, $counts, $outstanding_rows);
    }

    /**
     * $remaining_ids is whatever survived stripping the false club out — not trustworthy on its
     * own, since a trooper can have a second, real path to a club that happens to share a root
     * with the false membership (e.g. a false root-level Rebel Legion identifier alongside a
     * real Rebel Legion sub-org assignment). Always re-derives live eligibility and merges it in
     * rather than assuming the leftover is already correct. Both live and legacy results are
     * also run through excludePremature() so a club the trooper only joined after this shift
     * happened never gets credited either (see Fix409).
     *
     * @param  array<int, int>  $remaining_ids
     * @param  array<int, int>  $false_root_ids
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<int, array<int, Carbon>>  $join_signals
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function reresolveCredit(
        EventTrooper $event_trooper,
        array $remaining_ids,
        array $false_root_ids,
        Collection $all_orgs,
        array $join_signals,
        Collection $costume_club_map,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $shift_date = $event_trooper->event_shift?->shift_starts_at;

        $live_org_ids = $event_trooper->getEligibleCreditOrganizations()->pluck('id')->values()->all();

        if ($shift_date !== null)
        {
            $live_org_ids = $this->excludePremature($live_org_ids, $event_trooper->trooper_id, $shift_date, $all_orgs, $join_signals);
        }

        $merged_ids = collect($remaining_ids)->merge($live_org_ids)->unique()->values()->all();

        if (!empty($merged_ids))
        {
            if ($this->sameIds($merged_ids, $event_trooper->costume_organization_ids))
            {
                // A trooper can have a second, real path into the same root the false
                // membership shared (e.g. a genuine sub-org assignment). Stripping-then-merging
                // lands back on the exact value already stored — nothing to write.
                return;
            }

            $event_trooper->costume_organization_ids = $merged_ids;
            $event_trooper->saveQuietly();
            $counts[empty($remaining_ids) ? 'recovered_live' : 'corrected_partial_credit']++;

            return;
        }

        // legacy signup data can carry the same false club the membership did — filter it too
        $legacy_org_ids = collect($this->resolveLegacyOrgIds($event_trooper, $costume_club_map))
            ->reject(function (int $org_id) use ($false_root_ids, $all_orgs)
            {
                $node_path = $all_orgs->get($org_id)?->node_path;

                return $node_path !== null && in_array(Organization::rootIdFromPath($node_path), $false_root_ids, true);
            })
            ->values()
            ->all();

        if ($shift_date !== null)
        {
            $legacy_org_ids = $this->excludePremature($legacy_org_ids, $event_trooper->trooper_id, $shift_date, $all_orgs, $join_signals);
        }

        if (!empty($legacy_org_ids))
        {
            if ($this->sameIds($legacy_org_ids, $event_trooper->costume_organization_ids))
            {
                return;
            }

            $event_trooper->costume_organization_ids = $legacy_org_ids;
            $event_trooper->saveQuietly();
            $counts['recovered_legacy']++;

            return;
        }

        if (empty($event_trooper->costume_organization_ids))
        {
            return;
        }

        // couldn't resolve it either way — clear the false credit instead of leaving it in place
        $event_trooper->costume_organization_ids = [];
        $event_trooper->saveQuietly();

        $counts['skipped_no_eligible_org']++;
        $outstanding_rows[] = [
            'event_trooper_id' => $event_trooper->id,
            'trooper_name' => $event_trooper->trooper->display_name,
            'event_name' => $event_trooper->event_shift->event->name,
            'event_id' => $event_trooper->event_shift->event->id,
            'costume_name' => $event_trooper->costume?->name,
        ];
    }

    /** @param  array<int, int>  $a @param  array<int, int>  $b */
    private function sameIds(array $a, array $b): bool
    {
        $normalize = fn (array $ids) => collect($ids)->unique()->sort()->values()->all();

        return $normalize($a) === $normalize($b);
    }

    /** @return Collection<int, array{id: int, costume_club_id: int}> */
    private function buildCostumeClubMap(): Collection
    {
        if (!Schema::hasTable('event_sign_up') || !Schema::hasTable('costumes'))
        {
            return collect();
        }

        try
        {
            return collect($this->getCostumeClubMap())->keyBy('costume_club_id');
        }
        catch (Exception)
        {
            return collect();
        }
    }

    /**
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @return array<int, int>
     */
    private function resolveLegacyOrgIds(EventTrooper $event_trooper, Collection $costume_club_map): array
    {
        if ($costume_club_map->isEmpty())
        {
            return [];
        }

        $legacy_signup = DB::table('event_sign_up')
            ->join('costumes', 'costumes.id', '=', 'event_sign_up.costume')
            ->where('event_sign_up.troopid', $event_trooper->event_shift_id)
            ->where('event_sign_up.trooperid', $event_trooper->trooper_id)
            ->whereNotNull('costumes.club')
            ->selectRaw('costumes.club')
            ->first();

        if ($legacy_signup === null)
        {
            return [];
        }

        $club_ids = $this->expandDualClubIds([(int) $legacy_signup->club]);

        return collect($club_ids)
            ->map(fn (int $club_id) => $costume_club_map->get($club_id)['id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, int>>  $false_root_ids_by_trooper
     * @param  array<string, int>  $counts
     */
    private function removeFalseAchievements(array $false_root_ids_by_trooper, array &$counts): void
    {
        foreach ($false_root_ids_by_trooper as $trooper_id => $false_root_ids)
        {
            $removed = TrooperAchievement::query()
                ->where(TrooperAchievement::TROOPER_ID, $trooper_id)
                ->whereIn(TrooperAchievement::ORGANIZATION_ID, $false_root_ids)
                ->get();

            foreach ($removed as $achievement)
            {
                $achievement->forceDelete();
            }

            $counts['achievements_removed'] += $removed->count();
        }
    }

    /**
     * Runs site-wide, independent of the false-membership troopers above — a trooper can be
     * credited to the wrong club (or an extra one) for a specific shift without ever having a
     * false membership: they may have left the club the legacy tag names, or weren't yet a
     * second club's member when an earlier backfill assumed their current (dual) membership
     * applied retroactively. The legacy tag is the one signal that records which club a trooper
     * represented at that specific historical shift, so it wins whenever one exists.
     *
     * @param  array<int, array<int, int>>  $false_root_ids_by_trooper
     * @param  Collection<int, Organization>  $all_orgs
     * @param  array<string, int>  $counts
     */
    private function correctLegacyMismatchedCredit(array $false_root_ids_by_trooper, Collection $all_orgs, array &$counts): void
    {
        if (!Schema::hasTable('event_sign_up') || !Schema::hasTable('costumes'))
        {
            return;
        }

        $costume_club_map = $this->buildCostumeClubMap();

        if ($costume_club_map->isEmpty())
        {
            return;
        }

        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->chunkById(500, function ($event_troopers) use (&$counts, $false_root_ids_by_trooper, $all_orgs, $costume_club_map): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    $this->correctLegacyMismatchForRow($event_trooper, $false_root_ids_by_trooper, $all_orgs, $costume_club_map, $counts);
                }
            });
    }

    /**
     * @param  array<int, array<int, int>>  $false_root_ids_by_trooper
     * @param  Collection<int, Organization>  $all_orgs
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     */
    private function correctLegacyMismatchForRow(
        EventTrooper $event_trooper,
        array $false_root_ids_by_trooper,
        Collection $all_orgs,
        Collection $costume_club_map,
        array &$counts,
    ): void {
        $distinct_clubs = DB::table('event_sign_up')
            ->join('costumes', 'costumes.id', '=', 'event_sign_up.costume')
            ->where('event_sign_up.troopid', $event_trooper->event_shift_id)
            ->where('event_sign_up.trooperid', $event_trooper->trooper_id)
            ->whereNotNull('costumes.club')
            ->pluck('costumes.club')
            ->unique();

        if ($distinct_clubs->isEmpty())
        {
            return;
        }

        if ($distinct_clubs->count() > 1)
        {
            // More than one legacy signup row disagrees on the club for this shift — let a
            // human sort it out rather than guess.
            $counts['legacy_club_ambiguous']++;

            return;
        }

        $legacy_club_ids = $this->expandDualClubIds([(int) $distinct_clubs->first()]);
        $legacy_org_ids = collect($legacy_club_ids)
            ->map(fn (int $club_id) => $costume_club_map->get($club_id)['id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($legacy_org_ids->isEmpty())
        {
            // Unmapped legacy club code (e.g. "Other") — nothing we can resolve to.
            return;
        }

        $false_root_ids = $false_root_ids_by_trooper[$event_trooper->trooper_id] ?? [];

        $legacy_roots = $legacy_org_ids
            ->map(fn (int $id) => Organization::rootIdFromPath($all_orgs->get($id)?->node_path ?? ''))
            ->filter()
            ->unique()
            // A known-false membership can taint the legacy signup tag the same way it tainted
            // the membership itself (the old form forced a club selection on everyone) — never
            // let this step reintroduce a club correctCredit() already determined is false.
            ->reject(fn (int $root_id) => in_array($root_id, $false_root_ids, true))
            ->values()
            ->all();

        if (empty($legacy_roots))
        {
            return;
        }

        $current_ids = $event_trooper->costume_organization_ids ?? [];

        // Keep whatever's already stored under a club the legacy tag actually claims — preserves
        // sub-org specificity (e.g. a squad id) instead of flattening it to the bare root id.
        $kept = collect($current_ids)->filter(function (int $org_id) use ($legacy_roots, $all_orgs)
        {
            $node_path = $all_orgs->get($org_id)?->node_path;

            return $node_path !== null && in_array(Organization::rootIdFromPath($node_path), $legacy_roots, true);
        });

        $kept_roots = $kept
            ->map(fn (int $org_id) => Organization::rootIdFromPath($all_orgs->get($org_id)->node_path))
            ->unique()
            ->all();

        $missing_roots = array_diff($legacy_roots, $kept_roots);
        $resolved_ids = $kept->merge($missing_roots)->unique()->values()->all();

        if ($this->sameIds($resolved_ids, $current_ids))
        {
            return;
        }

        $event_trooper->costume_organization_ids = $resolved_ids;
        $event_trooper->saveQuietly();
        $counts['legacy_mismatch_corrected']++;
    }

    /** @param  array<string, int>  $counts */
    private function printSummary(array $counts): void
    {
        $this->command?->info('Fix408 complete:');
        $this->command?->info("  Memberships corrected:            {$counts['memberships_corrected']}");
        $this->command?->info("  Memberships flagged (ambiguous):  {$counts['memberships_ambiguous']}");
        $this->command?->info("  Credit rows scanned:              {$counts['credit_rows_scanned']}");
        $this->command?->info("  Corrected (partial credit kept):  {$counts['corrected_partial_credit']}");
        $this->command?->info("  Recovered (live eligibility):      {$counts['recovered_live']}");
        $this->command?->info("  Recovered (legacy fallback):       {$counts['recovered_legacy']}");
        $this->command?->info("  Skipped (no eligible org):         {$counts['skipped_no_eligible_org']}");
        $this->command?->info("  Achievement rows removed:          {$counts['achievements_removed']}");
        $this->command?->info("  Legacy-tag mismatches corrected:   {$counts['legacy_mismatch_corrected']}");
        $this->command?->info("  Legacy-tag ambiguous (skipped):    {$counts['legacy_club_ambiguous']}");
        $this->command?->newLine();
        $this->command?->info('  Run `php artisan tracker:calculate-trooper-achievements` next so any');
        $this->command?->info('  legitimately-earned club milestones are created fresh.');
    }

    /** @param  array<int, array<string, mixed>>  $outstanding_rows */
    private function emailOutstandingCredit(MagicBus $bus, array $outstanding_rows): void
    {
        if (empty($outstanding_rows))
        {
            return;
        }

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new Fix408OutstandingCredit($admin, $outstanding_rows));
        }
    }

    /** @param  array<int, array<string, mixed>>  $ambiguous_memberships */
    private function emailAmbiguousMemberships(MagicBus $bus, array $ambiguous_memberships): void
    {
        if (empty($ambiguous_memberships))
        {
            return;
        }

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new Fix408AmbiguousMemberships($admin, $ambiguous_memberships));
        }
    }
}
