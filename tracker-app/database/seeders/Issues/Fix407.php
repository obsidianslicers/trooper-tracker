<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Fix407OutstandingCredit;
use App\Models\EventTrooper;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy-data fallback for EventTrooper records Fix406 couldn't resolve.
 *
 * A small number of records have no current costume approval or membership to derive credit
 * from — usually troopers with no active club assignment at all (retired, command staff, N/A) —
 * so Fix406 skips them. Before giving up, this looks up the trooper's original signup for that
 * shift in the legacy (pre-2.0) event_sign_up/costumes tables, matched via
 * event_sign_up.troopid = event_shift_id and event_sign_up.trooperid = trooper_id (both ids
 * preserved 1:1 from the old tracker). Those tables still carry a per-costume "club" tag even
 * for signups the 2.0 import skipped (handler/command-staff/N/A costumes), so if it maps to a
 * real organization, credit gets backfilled from it.
 *
 * Only runs for rows Fix406's live resolver already gave up on — doesn't duplicate its
 * single/multi-club resolution. Run Fix406 first.
 */
class Fix407 extends Seeder
{
    use HasClubMaps;

    public function run(MagicBus $bus): void
    {
        $outstanding_rows = [];

        DB::transaction(function () use (&$outstanding_rows): void {
            $counts = [
                'scanned' => 0,
                'resolved_legacy_fallback' => 0,
                'skipped_no_eligible_org' => 0,
            ];

            $club_map = $this->buildClubMap();

            EventTrooper::query()
                ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
                ->whereNull(EventTrooper::ORGANIZATION_ID)
                ->where(function ($query): void {
                    $query->whereNull(EventTrooper::COSTUME_ORGANIZATION_IDS)
                        ->orWhereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, 0);
                })
                ->with(['trooper.trooper_costumes.organization_costume', 'trooper.trooper_assignments', 'costume', 'event_shift.event'])
                ->chunkById(200, function ($event_troopers) use (&$counts, &$outstanding_rows, $club_map): void {
                    foreach ($event_troopers as $event_trooper)
                    {
                        if ($event_trooper->getEligibleCreditParentOrganizations()->isNotEmpty())
                        {
                            // Fix406 already resolves (or will resolve) this row — not our job.
                            continue;
                        }

                        $counts['scanned']++;

                        $legacy_signup = $this->findLegacySignup($event_trooper);
                        $legacy_org_ids = $this->resolveLegacyOrgIds($legacy_signup, $club_map);

                        if (!empty($legacy_org_ids))
                        {
                            $event_trooper->costume_organization_ids = $legacy_org_ids;
                            $event_trooper->saveQuietly();
                            $counts['resolved_legacy_fallback']++;

                            continue;
                        }

                        $counts['skipped_no_eligible_org']++;
                        $outstanding_rows[] = [
                            'event_trooper_id' => $event_trooper->id,
                            'trooper_name' => $event_trooper->trooper->display_name,
                            'event_name' => $event_trooper->event_shift->event->name,
                            'event_id' => $event_trooper->event_shift->event->id,
                            'costume_name' => $event_trooper->costume?->name,
                            'legacy_note' => $this->buildLegacyNote($legacy_signup),
                        ];
                    }
                });

            $this->command?->info('Fix407 complete:');
            $this->command?->info("  Scanned (unresolved by Fix406):   {$counts['scanned']}");
            $this->command?->info("  Resolved (legacy fallback):       {$counts['resolved_legacy_fallback']}");
            $this->command?->info("  Skipped (no eligible org):        {$counts['skipped_no_eligible_org']}");
        });

        $this->emailOutstandingRowsToAdministrators($bus, $outstanding_rows);
    }

    /** @return Collection<int, array{id: int, costume_club_id: int}> */
    private function buildClubMap(): Collection
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
            // named clubs like "501st Legion" don't exist here, nothing to map against
            return collect();
        }
    }

    private function findLegacySignup(EventTrooper $event_trooper): ?object
    {
        if (!Schema::hasTable('event_sign_up') || !Schema::hasTable('costumes'))
        {
            return null;
        }

        return DB::table('event_sign_up')
            ->join('costumes', 'costumes.id', '=', 'event_sign_up.costume')
            ->where('event_sign_up.troopid', $event_trooper->event_shift_id)
            ->where('event_sign_up.trooperid', $event_trooper->trooper_id)
            ->selectRaw('costumes.costume, costumes.club')
            ->first();
    }

    /**
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $club_map
     * @return array<int, int>
     */
    private function resolveLegacyOrgIds(?object $legacy_signup, Collection $club_map): array
    {
        if ($legacy_signup === null || $legacy_signup->club === null || $club_map->isEmpty())
        {
            return [];
        }

        $club_ids = $this->expandDualClubIds([(int) $legacy_signup->club]);

        return collect($club_ids)
            ->map(fn (int $club_id) => $club_map->get($club_id)['id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function buildLegacyNote(?object $legacy_signup): string
    {
        if ($legacy_signup === null)
        {
            return 'No legacy signup record found for this shift.';
        }

        if ($legacy_signup->club === null)
        {
            return "Legacy costume \"{$legacy_signup->costume}\" has no club on record.";
        }

        return "Legacy costume \"{$legacy_signup->costume}\" (club not specific enough to credit).";
    }

    private function emailOutstandingRowsToAdministrators(MagicBus $bus, array $outstanding_rows): void
    {
        if (empty($outstanding_rows))
        {
            return;
        }

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new Fix407OutstandingCredit($admin, $outstanding_rows));
        }
    }
}
