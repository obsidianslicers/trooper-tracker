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
 * Corrects the false club memberships the old import created from a stray legacy identifier
 * (pX = 0). Only memberships already flagged retired/reserve are corrected — an active one may be
 * a real later join, so it's reported instead. TT2.0 credit on a corrected club is re-resolved;
 * TT1.0 credit is left to Fix407/Fix409. See docs/ISSUE_MIGRATIONS.md.
 */
class Fix408 extends Seeder
{
    use HasClubMaps;
    use HasSquadMaps;
    use ReportsToAdministrators;

    private LegacySignupCreditResolver $legacy;

    private HistoricalCreditResolver $resolver;

    /** @var Collection<int, Organization> */
    private Collection $all_orgs;

    /** @var array<int, array<int, int>> trooper_id => corrected (false) root club ids */
    private array $false_root_ids_by_trooper = [];

    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<int, array<string, mixed>> */
    private array $outstanding_rows = [];

    /** @var array<int, array<string, mixed>> */
    private array $ambiguous_memberships = [];

    public function run(MagicBus $bus): void
    {
        if (!Schema::hasTable('troopers'))
        {
            $this->command?->warn('Fix408 requires the legacy troopers table; nothing to do.');

            return;
        }

        $this->resetState();

        DB::transaction(function (): void
        {
            $this->repair();
            $this->printSummary();
        });

        $this->emailAdministrators($bus, Fix408OutstandingCredit::class, $this->outstanding_rows);
        $this->emailAdministrators(
            $bus,
            Fix408AmbiguousMemberships::class,
            $this->ambiguous_memberships,
        );
    }

    private function resetState(): void
    {
        $this->counts = array_fill_keys([
            'memberships_corrected', 'memberships_ambiguous', 'credit_rows_scanned',
            'tt1_rows_deferred', 'corrected_partial_credit', 'recovered',
            'cleared_outstanding', 'achievements_removed',
        ], 0);
        $this->false_root_ids_by_trooper = [];
        $this->outstanding_rows = [];
        $this->ambiguous_memberships = [];
    }

    private function repair(): void
    {
        $this->legacy = LegacySignupCreditResolver::load();
        $this->all_orgs = Organization::all([
            Organization::ID,
            Organization::NAME,
            Organization::NODE_PATH,
        ])->keyBy(Organization::ID);

        $this->auditMemberships();
        $this->auditSquadMemberships();

        // built after the corrections so the corrected memberships no longer count as evidence
        $false_roots = $this->false_root_ids_by_trooper;
        $this->resolver = HistoricalCreditResolver::load($this->legacy, $false_roots);

        $this->correctCredit();
        $this->removeFalseAchievements();
    }

    private function auditMemberships(): void
    {
        $club_map = $this->buildOrganizationClubMap();

        if ($club_map->isEmpty())
        {
            return;
        }

        foreach (DB::table('troopers')->get() as $legacy_trooper)
        {
            foreach ($club_map as $club)
            {
                $mismatch = $this->findMembershipMismatch($legacy_trooper, $club);

                if ($mismatch !== null)
                {
                    $this->handleMismatch($mismatch);
                }
            }
        }
    }

    /** @param  array<string, mixed>  $mismatch */
    private function handleMismatch(array $mismatch): void
    {
        $already_flagged = [MembershipStatus::RETIRED, MembershipStatus::RESERVE];

        if (!in_array($mismatch['membership_status'], $already_flagged, true))
        {
            // active, pending, denied, etc. — may be a real later join, let a human decide
            $this->ambiguous_memberships[] = $this->buildAmbiguousReportRow($mismatch);
            $this->counts['memberships_ambiguous']++;

            return;
        }

        $this->correctMembership($mismatch['trooper_organization'], $mismatch['assignment']);
        $this->false_root_ids_by_trooper[$mismatch['trooper_id']][] = $mismatch['organization_id'];

        if (!$mismatch['already_corrected'])
        {
            $this->counts['memberships_corrected']++;
        }
    }

    /**
     * Same bug, one level down: assignUnit() granted a Florida Garrison squad membership from the
     * legacy `squad` field alone, without checking p501. Every squad is a 501st unit.
     */
    private function auditSquadMemberships(): void
    {
        $p501_org = Organization::firstWhere(Organization::NAME, '501st Legion');
        $squad_map = $this->buildSquadMap();

        if ($p501_org === null || $squad_map->isEmpty())
        {
            return;
        }

        foreach (DB::table('troopers')->where('p501', 0)->get(['id', 'squad']) as $legacy_trooper)
        {
            $assignment = $this->findFalseSquadAssignment($legacy_trooper, $squad_map);

            if ($assignment !== null)
            {
                $this->clearAssignmentMembership($assignment);
                $this->false_root_ids_by_trooper[$legacy_trooper->id][] = $p501_org->id;
                $this->counts['memberships_corrected']++;
            }
        }
    }

    /** @param  Collection<int, array{id: int}>  $squad_map */
    private function findFalseSquadAssignment(
        object $legacy_trooper,
        Collection $squad_map,
    ): ?TrooperAssignment {
        $squad = $squad_map->get($legacy_trooper->squad);

        if ($squad === null)
        {
            return null;
        }

        return TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $legacy_trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $squad['id'])
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->first();
    }

    /** @return Collection<int, array{id: int}> */
    private function buildSquadMap(): Collection
    {
        try
        {
            return collect($this->getSquadMap());
        }
        catch (RuntimeException)
        {
            return collect();
        }
    }

    /**
     * @param  array<string, mixed>  $mismatch
     * @return array<string, mixed>
     */
    private function buildAmbiguousReportRow(array $mismatch): array
    {
        $trooper_id = $mismatch['trooper_id'];
        $organization_id = $mismatch['organization_id'];
        [$tt1_rows, $tt2_rows] = $this->countCreditedRows($trooper_id, $organization_id);

        return [
            'trooper_id' => $trooper_id,
            'trooper_name' => $mismatch['trooper_name'],
            'organization_name' => $this->all_orgs->get($organization_id)?->name ?? 'Unknown',
            'membership_status' => $mismatch['membership_status']->value,
            'event_trooper_row_count' => $tt1_rows + $tt2_rows,
            'tt1_row_count' => $tt1_rows,
            'tt2_row_count' => $tt2_rows,
            'achievement_row_count' => TrooperAchievement::query()
                ->where(TrooperAchievement::TROOPER_ID, $trooper_id)
                ->where(TrooperAchievement::ORGANIZATION_ID, $organization_id)
                ->count(),
        ];
    }

    /**
     * Credited shifts riding on a membership, split by signup origin: TT1.0 signups are judged
     * by their legacy signup (Fix407/409), TT2.0 signups by this membership.
     *
     * @return array{0: int, 1: int}
     */
    private function countCreditedRows(int $trooper_id, int $root_org_id): array
    {
        $rows = EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $trooper_id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->get([EventTrooper::EVENT_SHIFT_ID, EventTrooper::COSTUME_ORGANIZATION_IDS])
            ->filter(fn (EventTrooper $row) => collect($row->costume_organization_ids)->contains(
                fn (int $org_id) => $this->rootOf($org_id) === $root_org_id
            ));

        $tt1_rows = $rows
            ->filter(fn (EventTrooper $row) => $this->legacy->hasSignup(
                $row->event_shift_id,
                $trooper_id,
            ))
            ->count();

        return [$tt1_rows, $rows->count() - $tt1_rows];
    }

    private function rootOf(int $org_id): ?int
    {
        $node_path = $this->all_orgs->get($org_id)?->node_path;

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
        if (!$this->hasStrayIdentifier($legacy_trooper, $club))
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

    /** @param  array{id: int, permission_column: string, identity: string}  $club */
    private function hasStrayIdentifier(object $legacy_trooper, array $club): bool
    {
        $identifier = $club['identity'] !== '' ? $legacy_trooper->{$club['identity']} : null;
        $has_identifier = $identifier !== null && $identifier !== '' && $identifier !== '0';

        return (int) $legacy_trooper->{$club['permission_column']} === 0 && $has_identifier;
    }

    private function correctMembership(
        TrooperOrganization $trooper_org,
        ?TrooperAssignment $assignment,
    ): void {
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

    private function correctCredit(): void
    {
        if (empty($this->false_root_ids_by_trooper))
        {
            return;
        }

        EventTrooper::query()
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereIn(EventTrooper::TROOPER_ID, array_keys($this->false_root_ids_by_trooper))
            ->whereJsonLength(EventTrooper::COSTUME_ORGANIZATION_IDS, '>', 0)
            ->with(['trooper', 'costume', 'event_shift.event'])
            ->chunkById(200, function ($event_troopers): void
            {
                foreach ($event_troopers as $event_trooper)
                {
                    $this->correctCreditForRow($event_trooper);
                }
            });
    }

    private function correctCreditForRow(EventTrooper $event_trooper): void
    {
        $false_root_ids = $this->false_root_ids_by_trooper[$event_trooper->trooper_id];
        $is_false = fn (int $org_id) => in_array(
            $this->resolver->rootOf($org_id),
            $false_root_ids,
            true,
        );

        if (!collect($event_trooper->costume_organization_ids)->contains($is_false))
        {
            return;
        }

        $this->counts['credit_rows_scanned']++;

        if ($this->resolver->isTt1Signup($event_trooper))
        {
            // The legacy signup decides TT1.0 credit, not membership — Fix407/Fix409 own it.
            $this->counts['tt1_rows_deferred']++;

            return;
        }

        $this->stripFalseCredit($event_trooper, $is_false);
    }

    /** @param  callable(int): bool  $is_false */
    private function stripFalseCredit(EventTrooper $event_trooper, callable $is_false): void
    {
        $remaining_ids = collect($event_trooper->costume_organization_ids)
            ->reject($is_false)
            ->values()
            ->all();

        if (!empty($remaining_ids))
        {
            $event_trooper->costume_organization_ids = $remaining_ids;
            $event_trooper->saveQuietly();
            $this->counts['corrected_partial_credit']++;

            return;
        }

        $this->reresolveCredit($event_trooper, $is_false);
    }

    /** @param  callable(int): bool  $is_false */
    private function reresolveCredit(EventTrooper $event_trooper, callable $is_false): void
    {
        $resolution = $this->resolver->expectedCredit($event_trooper);
        $resolved_ids = collect($resolution->org_ids)->reject($is_false)->values()->all();

        $event_trooper->costume_organization_ids = $resolved_ids;
        $event_trooper->saveQuietly();

        if (!empty($resolved_ids))
        {
            $this->counts['recovered']++;

            return;
        }

        // couldn't resolve it — clear the false credit rather than leave it in place
        $reason = $resolution->resolved
            ? 'Only the false club was supported.'
            : $resolution->reason;
        $this->counts['cleared_outstanding']++;
        $this->outstanding_rows[] = $this->reportRow($event_trooper, $reason);
    }

    /**
     * Hard-deletes: the table's unique index includes soft-deleted rows, so a soft-deleted
     * milestone would block the recalculation from ever recreating a legitimate one.
     */
    private function removeFalseAchievements(): void
    {
        foreach ($this->false_root_ids_by_trooper as $trooper_id => $false_root_ids)
        {
            $removed = TrooperAchievement::withTrashed()
                ->where(TrooperAchievement::TROOPER_ID, $trooper_id)
                ->whereIn(TrooperAchievement::ORGANIZATION_ID, $false_root_ids)
                ->get();

            foreach ($removed as $achievement)
            {
                $achievement->forceDelete();
            }

            $this->counts['achievements_removed'] += $removed->count();
        }
    }

    private function printSummary(): void
    {
        $this->printCounts('Fix408 complete:', [
            'memberships_corrected' => 'Memberships corrected',
            'memberships_ambiguous' => 'Memberships flagged (ambiguous)',
            'credit_rows_scanned' => 'Credit rows on a false club',
            'tt1_rows_deferred' => 'TT1.0 rows left to Fix407/409',
            'corrected_partial_credit' => 'Corrected (other credit kept)',
            'recovered' => 'Recovered (TT2.0 history)',
            'cleared_outstanding' => 'Cleared (admin review)',
            'achievements_removed' => 'Achievement rows removed',
        ], $this->counts);
        $this->printRecalculateHint();
    }
}
