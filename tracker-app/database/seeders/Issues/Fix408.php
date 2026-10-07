<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Mail\Fix408AmbiguousMemberships;
use App\Mail\Fix408OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Database\Seeders\FloridaGarrison\Support\LegacySignupCreditResolver;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\FloridaGarrison\Traits\HasSquadMaps;
use Database\Seeders\Issues\Concerns\ReportsToAdministrators;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Corrects the Florida Garrison import's false club memberships.
 *
 * TrooperOrganizationSeeder used to grant club membership from a non-empty legacy identity field
 * (tkid, rebelforum, ...) alone, without checking the club's own permission flag (p501, pRebel,
 * ...). The old signup form required a TKID-style value from everyone regardless of club, so
 * plenty of troopers picked up a membership for a club they were never in. Once Fix242's
 * orphaned-TrooperOrganization repair ran, some of those also got a real TrooperAssignment, which
 * fed troop credit and club-scoped achievements. The importer is fixed; this repairs existing data.
 *
 * Membership is corrected conservatively: only (trooper, club) pairs whose permission flag is 0
 * and whose tt_trooper_organizations row is already retired/reserve — someone already flagged it.
 * Pairs still "active" are left alone and reported, since a pX = 0 flag only means "not a member
 * at the TT2.0 launch"; the trooper may have joined for real since.
 *
 * Credit is a separate question. For corrected pairs, TT2.0 signups crediting the false club are
 * re-resolved without it. TT1.0 signups are left to Fix407/Fix409, because their credit comes
 * from the legacy signup, not from membership.
 */
class Fix408 extends Seeder
{
    use HasClubMaps;
    use HasSquadMaps;
    use ReportsToAdministrators;

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
                'tt1_rows_deferred' => 0,
                'corrected_partial_credit' => 0,
                'recovered' => 0,
                'cleared_outstanding' => 0,
                'achievements_removed' => 0,
            ];

            $legacy = LegacySignupCreditResolver::load();
            $all_orgs = Organization::all([Organization::ID, Organization::NAME, Organization::NODE_PATH])->keyBy(Organization::ID);

            [$false_root_ids_by_trooper, $ambiguous_memberships] = $this->auditMemberships($counts, $all_orgs, $legacy);
            $this->auditSquadMemberships($false_root_ids_by_trooper, $counts);

            $resolver = HistoricalCreditResolver::load($legacy, $false_root_ids_by_trooper);

            $outstanding_credit_rows = $this->correctCredit($false_root_ids_by_trooper, $resolver, $counts);
            $this->removeFalseAchievements($false_root_ids_by_trooper, $counts);

            $this->printSummary($counts);
        });

        $this->emailAdministrators($bus, Fix408OutstandingCredit::class, $outstanding_credit_rows);
        $this->emailAdministrators($bus, Fix408AmbiguousMemberships::class, $ambiguous_memberships);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  Collection<int, Organization>  $all_orgs
     * @return array{0: array<int, array<int, int>>, 1: array<int, array<string, mixed>>}
     */
    private function auditMemberships(array &$counts, Collection $all_orgs, LegacySignupCreditResolver $legacy): array
    {
        $club_map = $this->buildOrganizationClubMap();
        $false_root_ids_by_trooper = [];
        $ambiguous_memberships = [];

        if ($club_map->isEmpty())
        {
            return [$false_root_ids_by_trooper, $ambiguous_memberships];
        }

        foreach (DB::table('troopers')->get() as $legacy_trooper)
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
                    // active, pending, denied, etc. — may be a real later join, let a human decide
                    $ambiguous_memberships[] = $this->buildAmbiguousReportRow($mismatch, $all_orgs, $legacy);
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
     * Same bug, one level down: assignUnit() granted a Florida Garrison squad membership from the
     * legacy `squad` field alone, also without checking p501. All of HasSquadMaps' squads are
     * 501st units, so a correction here counts toward the 501st root id.
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

        foreach (DB::table('troopers')->where('p501', 0)->get(['id', 'squad']) as $legacy_trooper)
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
                ->first();

            if ($assignment === null)
            {
                continue;
            }

            $this->clearAssignmentMembership($assignment);
            $false_root_ids_by_trooper[$legacy_trooper->id][] = $p501_org->id;
            $counts['memberships_corrected']++;
        }
    }

    /**
     * @param  array<string, mixed>  $mismatch
     * @param  Collection<int, Organization>  $all_orgs
     * @return array<string, mixed>
     */
    private function buildAmbiguousReportRow(array $mismatch, Collection $all_orgs, LegacySignupCreditResolver $legacy): array
    {
        [$tt1_rows, $tt2_rows] = $this->countCreditedRows($mismatch['trooper_id'], $mismatch['organization_id'], $all_orgs, $legacy);

        return [
            'trooper_id' => $mismatch['trooper_id'],
            'trooper_name' => $mismatch['trooper_name'],
            'organization_name' => $all_orgs->get($mismatch['organization_id'])?->name ?? 'Unknown',
            'membership_status' => $mismatch['membership_status']->value,
            'event_trooper_row_count' => $tt1_rows + $tt2_rows,
            'tt1_row_count' => $tt1_rows,
            'tt2_row_count' => $tt2_rows,
            'achievement_row_count' => TrooperAchievement::query()
                ->where(TrooperAchievement::TROOPER_ID, $mismatch['trooper_id'])
                ->where(TrooperAchievement::ORGANIZATION_ID, $mismatch['organization_id'])
                ->count(),
        ];
    }

    /**
     * Credited shifts riding on a membership, split by signup origin: TT1.0 signups are judged
     * by their legacy signup (Fix407/409), TT2.0 signups by this membership.
     *
     * @param  Collection<int, Organization>  $all_orgs
     * @return array{0: int, 1: int}
     */
    private function countCreditedRows(int $trooper_id, int $root_org_id, Collection $all_orgs, LegacySignupCreditResolver $legacy): array
    {
        $rows = EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $trooper_id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->get([EventTrooper::EVENT_SHIFT_ID, EventTrooper::COSTUME_ORGANIZATION_IDS])
            ->filter(fn (EventTrooper $row) => collect($row->costume_organization_ids)->contains(
                fn (int $org_id) => $this->rootOf($org_id, $all_orgs) === $root_org_id
            ));

        $tt1_rows = $rows->filter(fn (EventTrooper $row) => $legacy->hasSignup($row->event_shift_id, $trooper_id))->count();

        return [$tt1_rows, $rows->count() - $tt1_rows];
    }

    /** @param  Collection<int, Organization>  $all_orgs */
    private function rootOf(int $org_id, Collection $all_orgs): ?int
    {
        $node_path = $all_orgs->get($org_id)?->node_path;

        return $node_path === null ? null : Organization::rootIdFromPath($node_path);
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
     * Keys off the tt_trooper_organizations row itself, trashed or not, so a re-run keeps
     * including already-corrected troopers in the credit/achievement passes without re-counting
     * or re-deleting the membership.
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
            return null;
        }

        return [
            'trooper_id' => (int) $legacy_trooper->id,
            'trooper_name' => $legacy_trooper->name,
            'organization_id' => $club['id'],
            'membership_status' => $trooper_org->membership_status,
            'trooper_organization' => $trooper_org,
            'already_corrected' => $trooper_org->trashed(),
            'assignment' => TrooperAssignment::query()
                ->where(TrooperAssignment::TROOPER_ID, $legacy_trooper->id)
                ->where(TrooperAssignment::ORGANIZATION_ID, $club['id'])
                ->first(),
        ];
    }

    private function correctMembership(TrooperOrganization $trooper_org, ?TrooperAssignment $assignment): void
    {
        if (!$trooper_org->trashed())
        {
            $trooper_org->delete();
        }

        if ($assignment !== null && $assignment->is_member)
        {
            $this->clearAssignmentMembership($assignment);
        }
    }

    private function clearAssignmentMembership(TrooperAssignment $assignment): void
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
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function correctCredit(array $false_root_ids_by_trooper, HistoricalCreditResolver $resolver, array &$counts): array
    {
        $outstanding_rows = [];

        if (empty($false_root_ids_by_trooper))
        {
            return $outstanding_rows;
        }

        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereIn(EventTrooper::TROOPER_ID, array_keys($false_root_ids_by_trooper))
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->with(['trooper', 'costume', 'event_shift.event'])
            ->chunkById(200, function ($event_troopers) use (&$outstanding_rows, &$counts, $false_root_ids_by_trooper, $resolver): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    $false_root_ids = $false_root_ids_by_trooper[$event_trooper->trooper_id];
                    $this->correctCreditForRow($event_trooper, $false_root_ids, $resolver, $counts, $outstanding_rows);
                }
            });

        return $outstanding_rows;
    }

    /**
     * @param  array<int, int>  $false_root_ids
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function correctCreditForRow(
        EventTrooper $event_trooper,
        array $false_root_ids,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $is_false = fn (int $org_id) => in_array($resolver->rootOf($org_id), $false_root_ids, true);
        $original_ids = $event_trooper->costume_organization_ids;

        if (!collect($original_ids)->contains($is_false))
        {
            return;
        }

        $counts['credit_rows_scanned']++;

        if ($resolver->isTt1Signup($event_trooper))
        {
            // The legacy signup decides TT1.0 credit, not membership — Fix407/Fix409 own it.
            $counts['tt1_rows_deferred']++;

            return;
        }

        $remaining_ids = collect($original_ids)->reject($is_false)->values()->all();

        if (!empty($remaining_ids))
        {
            $event_trooper->costume_organization_ids = $remaining_ids;
            $event_trooper->saveQuietly();
            $counts['corrected_partial_credit']++;

            return;
        }

        $this->reresolveCredit($event_trooper, $is_false, $resolver, $counts, $outstanding_rows);
    }

    /**
     * @param  callable(int): bool  $is_false
     * @param  array<string, int>  $counts
     * @param  array<int, array<string, mixed>>  $outstanding_rows
     */
    private function reresolveCredit(
        EventTrooper $event_trooper,
        callable $is_false,
        HistoricalCreditResolver $resolver,
        array &$counts,
        array &$outstanding_rows,
    ): void {
        $resolution = $resolver->expectedCredit($event_trooper);
        $resolved_ids = collect($resolution->org_ids)->reject($is_false)->values()->all();

        $event_trooper->costume_organization_ids = $resolved_ids;
        $event_trooper->saveQuietly();

        if (!empty($resolved_ids))
        {
            $counts['recovered']++;

            return;
        }

        // couldn't resolve it — clear the false credit rather than leave it in place
        $counts['cleared_outstanding']++;
        $outstanding_rows[] = $this->reportRow(
            $event_trooper,
            $resolution->resolved ? 'Only the false club was supported.' : $resolution->reason,
        );
    }

    /**
     * Hard-deletes, not soft-deletes: the table's unique index on (trooper_id, type,
     * organization_coalesce_id) doesn't exclude soft-deleted rows, so a soft-deleted row would
     * permanently block any future legitimate milestone for that trooper/type/club.
     *
     * @param  array<int, array<int, int>>  $false_root_ids_by_trooper
     * @param  array<string, int>  $counts
     */
    private function removeFalseAchievements(array $false_root_ids_by_trooper, array &$counts): void
    {
        foreach ($false_root_ids_by_trooper as $trooper_id => $false_root_ids)
        {
            $removed = TrooperAchievement::withTrashed()
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
        $this->command?->info("  Credit rows on a false club:      {$counts['credit_rows_scanned']}");
        $this->command?->info("  TT1.0 rows left to Fix407/409:    {$counts['tt1_rows_deferred']}");
        $this->command?->info("  Corrected (other credit kept):    {$counts['corrected_partial_credit']}");
        $this->command?->info("  Recovered (TT2.0 history):        {$counts['recovered']}");
        $this->command?->info("  Cleared (admin review):           {$counts['cleared_outstanding']}");
        $this->command?->info("  Achievement rows removed:         {$counts['achievements_removed']}");
        $this->command?->newLine();
        $this->command?->info('  Run `php artisan tracker:calculate-trooper-achievements` next so any');
        $this->command?->info('  legitimately-earned club milestones are created fresh.');
    }
}
