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
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Corrects the Florida Garrison import's false club-membership bug.
 *
 * TrooperOrganizationSeeder determined club membership purely from whether a legacy identity
 * field (tkid, rebelforum, ...) was non-empty, never checking the club's own permission flag
 * (p501, pRebel, ...). The old tracker required every trooper to fill in a TKID-style field in
 * its unified signup form regardless of actual club, so troopers ended up with an active
 * membership — and, once Fix242's orphaned-TrooperOrganization repair ran, an active
 * TrooperAssignment — for a club they never belonged to. That false membership then fed troop
 * credit (EventTrooper::getEligibleCreditOrganizations()) and club-scoped achievement milestones
 * for years, independent of this seeder's own Fix406/Fix407 backfill.
 *
 * Only (trooper, club) pairs where the legacy permission flag is 0 AND the current
 * tt_trooper_organizations row for that club is already retired/reserve (not active) are
 * corrected automatically — someone already flagged the membership as wrong, it just never
 * propagated to the TrooperAssignment row. Pairs where that row is still "active" are reported
 * to administrators untouched, since the trooper could have legitimately joined later.
 */
class Fix408 extends Seeder
{
    use HasClubMaps;

    public function run(MagicBus $bus): void
    {
        if (!Schema::hasTable('troopers'))
        {
            $this->command?->warn('Fix408 requires the legacy troopers table; nothing to do.');

            return;
        }

        $outstanding_credit_rows = [];
        $ambiguous_memberships = [];

        DB::transaction(function () use (&$outstanding_credit_rows, &$ambiguous_memberships): void {
            $counts = [
                'memberships_corrected' => 0,
                'memberships_ambiguous' => 0,
                'credit_rows_scanned' => 0,
                'corrected_partial_credit' => 0,
                'recovered_live' => 0,
                'recovered_legacy' => 0,
                'skipped_no_eligible_org' => 0,
                'achievements_removed' => 0,
            ];

            $all_orgs = Organization::all([Organization::ID, Organization::NODE_PATH])->keyBy(Organization::ID);

            [$false_root_ids_by_trooper, $ambiguous_memberships] = $this->auditMemberships($counts, $all_orgs);

            $outstanding_credit_rows = $this->correctCredit($false_root_ids_by_trooper, $all_orgs, $counts);
            $this->removeFalseAchievements($false_root_ids_by_trooper, $counts);

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

                if ($mismatch['membership_status'] === MembershipStatus::ACTIVE)
                {
                    $ambiguous_memberships[] = $this->buildAmbiguousReportRow($mismatch, $all_orgs);
                    $counts['memberships_ambiguous']++;

                    continue;
                }

                $this->correctAssignment($mismatch['assignment']);
                $false_root_ids_by_trooper[$mismatch['trooper_id']][] = $mismatch['organization_id'];
                $counts['memberships_corrected']++;
            }
        }

        return [$false_root_ids_by_trooper, $ambiguous_memberships];
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
            ->filter(function (EventTrooper $event_trooper) use ($root_org_id, $all_orgs) {
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

        $assignment = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $legacy_trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $club['id'])
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereNull(TrooperAssignment::DELETED_AT)
            ->first();

        if ($assignment === null)
        {
            return null;
        }

        $trooper_org = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $legacy_trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $club['id'])
            ->whereNull(TrooperOrganization::DELETED_AT)
            ->first();

        return [
            'trooper_id' => $legacy_trooper->id,
            'trooper_name' => $legacy_trooper->name,
            'organization_id' => $club['id'],
            'membership_status' => $trooper_org?->membership_status ?? MembershipStatus::ACTIVE,
            'assignment' => $assignment,
        ];
    }

    private function correctAssignment(TrooperAssignment $assignment): void
    {
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
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function correctCredit(array $false_root_ids_by_trooper, Collection $all_orgs, array &$counts): array
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
                $costume_club_map,
            ): void {
                foreach ($event_troopers as $event_trooper)
                {
                    $this->correctCreditForRow(
                        $event_trooper,
                        $false_root_ids_by_trooper[$event_trooper->trooper_id],
                        $all_orgs,
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
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function correctCreditForRow(
        EventTrooper $event_trooper,
        array $false_root_ids,
        Collection $all_orgs,
        Collection $costume_club_map,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $original_ids = $event_trooper->costume_organization_ids;

        $remaining_ids = collect($original_ids)
            ->reject(function (int $org_id) use ($false_root_ids, $all_orgs) {
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

        if ($remaining_ids->isNotEmpty())
        {
            $event_trooper->costume_organization_ids = $remaining_ids->all();
            $event_trooper->saveQuietly();
            $counts['corrected_partial_credit']++;

            return;
        }

        $this->reresolveCredit($event_trooper, $costume_club_map, $counts, $outstanding_rows);
    }

    /**
     * @param  Collection<int, array{id: int, costume_club_id: int}>  $costume_club_map
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function reresolveCredit(
        EventTrooper $event_trooper,
        Collection $costume_club_map,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $live_org_ids = $event_trooper->getEligibleCreditOrganizations()->pluck('id')->values()->all();

        if (!empty($live_org_ids))
        {
            $event_trooper->costume_organization_ids = $live_org_ids;
            $event_trooper->saveQuietly();
            $counts['recovered_live']++;

            return;
        }

        $legacy_org_ids = $this->resolveLegacyOrgIds($event_trooper, $costume_club_map);

        if (!empty($legacy_org_ids))
        {
            $event_trooper->costume_organization_ids = $legacy_org_ids;
            $event_trooper->saveQuietly();
            $counts['recovered_legacy']++;

            return;
        }

        $counts['skipped_no_eligible_org']++;
        $outstanding_rows[] = [
            'event_trooper_id' => $event_trooper->id,
            'trooper_name' => $event_trooper->trooper->display_name,
            'event_name' => $event_trooper->event_shift->event->name,
            'event_id' => $event_trooper->event_shift->event->id,
            'costume_name' => $event_trooper->costume?->name,
        ];
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
