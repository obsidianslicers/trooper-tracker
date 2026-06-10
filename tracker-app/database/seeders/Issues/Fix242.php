<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Enums\TrooperRequestStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Migrates legacy join-request data into tt_trooper_requests and repairs bad membership records.
 *
 * Legacy data problems handled:
 *
 *   1. Pending TrooperOrganization rows — old code stored pending join requests as TrooperOrganization
 *      records. Migrate each to a TrooperRequest (pending) and soft-delete the old row.
 *
 *   2. Denied TrooperOrganization rows — same as above for denied status.
 *      Migrate to TrooperRequest (denied) with null denial_reason (was never persisted).
 *
 *   3. Active TrooperOrganization rows pointing to a sub-org — bad records from before the observer
 *      was added. Repair: create TrooperOrganization at the primary club + TrooperAssignment at the
 *      sub-org, then soft-delete the incorrect sub-org membership row.
 *
 * TrooperAssignment integrity repairs (unchanged from original Fix242):
 *
 *   4. is_member=true with deleted_at set — flag was not cleared before soft-delete.
 *
 *   5. is_member=false with deleted_at null and no moderator/notify purpose — flag cleared but
 *      the row was never soft-deleted.
 *
 *   6. Multiple active assignments in the same primary-club hierarchy — only one allowed.
 *      Keep the most recently updated; soft-delete the rest.
 *
 *   7. Active TrooperOrganization at primary club with no active TrooperAssignment anywhere in
 *      that club's hierarchy — create the missing assignment at the primary club.
 */
class Fix242 extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void
        {
            $counts = [
                'pending_migrated' => $this->migratePendingTrooperOrganizations(),
                'denied_migrated' => $this->migrateDeniedTrooperOrganizations(),
                'sub_org_repaired' => $this->repairActiveSubOrgMemberships(),
                'denied_requests' => $this->denyPendingJoinRequestsForDeniedTroopers(),
                'flag_cleared' => $this->fixFlagNotClearedOnDelete(),
                'not_deleted' => $this->fixFlagClearedButNotDeleted(),
                'hierarchy_dupes' => $this->fixHierarchyDuplicates(),
                'orphaned_trooper_org' => $this->fixOrphanedTrooperOrganizations(),
            ];

            $this->command?->info('Fix242 complete:');
            $this->command?->info("  Migrated {$counts['pending_migrated']} pending TrooperOrganization row(s) to TrooperRequest.");
            $this->command?->info("  Migrated {$counts['denied_migrated']} denied TrooperOrganization row(s) to TrooperRequest.");
            $this->command?->info("  Repaired {$counts['sub_org_repaired']} active sub-org TrooperOrganization row(s).");
            $this->command?->info("  Denied {$counts['denied_requests']} pending TrooperRequest row(s) for denied troopers.");
            $this->command?->info("  Cleared is_member flag on {$counts['flag_cleared']} soft-deleted assignment row(s).");
            $this->command?->info("  Soft-deleted {$counts['not_deleted']} cleared-but-not-deleted assignment row(s).");
            $this->command?->info("  Resolved {$counts['hierarchy_dupes']} hierarchy duplicate assignment(s).");
            $this->command?->info("  Created {$counts['orphaned_trooper_org']} missing TrooperAssignment row(s) for orphaned memberships.");
        });
    }

    /**
     * Migrate pending TrooperOrganization rows to TrooperRequest (pending).
     *
     * These are old join requests stored in the wrong table. Preserves trooper,
     * requested organization, primary club, and identifier.
     */
    private function migratePendingTrooperOrganizations(): int
    {
        return $this->migrateTrooperOrganizationsByStatus(
            MembershipStatus::PENDING,
            TrooperRequestStatus::PENDING,
        );
    }

    /**
     * Migrate denied TrooperOrganization rows to TrooperRequest (denied).
     *
     * Denial reason was never persisted in the old model, so denial_reason is left null.
     */
    private function migrateDeniedTrooperOrganizations(): int
    {
        return $this->migrateTrooperOrganizationsByStatus(
            MembershipStatus::DENIED,
            TrooperRequestStatus::DENIED,
        );
    }

    private function migrateTrooperOrganizationsByStatus(
        MembershipStatus $from_status,
        TrooperRequestStatus $to_status,
    ): int {
        $migrated = 0;

        $rows = TrooperOrganization::where(TrooperOrganization::MEMBERSHIP_STATUS, $from_status)
            ->whereNull(TrooperOrganization::DELETED_AT)
            ->get();

        foreach ($rows as $trooper_org)
        {
            $primary_club = $trooper_org->organization->getPrimaryClub();
            $requested_org = $this->requestedOrganizationForLegacyMembership($trooper_org, $primary_club);

            $this->upsertJoinRequest($trooper_org, $requested_org, $primary_club, $to_status);
            $this->clearMemberAssignmentsInHierarchy($primary_club, $trooper_org->trooper_id);

            $trooper_org->delete();

            $migrated++;
        }

        return $migrated;
    }

    private function requestedOrganizationForLegacyMembership(
        TrooperOrganization $trooper_org,
        Organization $primary_club,
    ): Organization {
        $assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereNull(TrooperAssignment::DELETED_AT)
            ->whereHas('organization', function ($query) use ($primary_club): void
            {
                $query->where(Organization::NODE_PATH, 'like', $primary_club->node_path . '%');
            })
            ->with('organization')
            ->orderByDesc(TrooperAssignment::UPDATED_AT)
            ->first();

        return $assignment?->organization ?? $trooper_org->organization;
    }

    private function upsertJoinRequest(
        TrooperOrganization $trooper_org,
        Organization $requested_org,
        Organization $primary_club,
        TrooperRequestStatus $status,
    ): void {
        $trooper_request = TrooperRequest::withTrashed()
            ->where(TrooperRequest::TROOPER_ID, $trooper_org->trooper_id)
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $primary_club->id)
            ->where(TrooperRequest::STATUS, $status)
            ->first() ?? new TrooperRequest;

        if ($trooper_request->exists && $trooper_request->trashed())
        {
            $trooper_request->restore();
        }

        $trooper_request->trooper_id = $trooper_org->trooper_id;
        $trooper_request->organization_id = $requested_org->id;
        $trooper_request->primary_organization_id = $primary_club->id;
        $trooper_request->identifier = $trooper_org->identifier;
        $trooper_request->status = $status;

        $trooper_request->save();
    }

    private function clearMemberAssignmentsInHierarchy(Organization $primary_club, int $trooper_id): int
    {
        $ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereNull(TrooperAssignment::DELETED_AT)
            ->whereHas('organization', function ($query) use ($primary_club): void
            {
                $query->where(Organization::NODE_PATH, 'like', $primary_club->node_path . '%');
            })
            ->pluck(TrooperAssignment::ID);

        if ($ids->isEmpty())
        {
            return 0;
        }

        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)
            ->update([TrooperAssignment::IS_MEMBER => false]);
        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->delete();

        return $ids->count();
    }

    /**
     * Repair active TrooperOrganization rows that point to a sub-org instead of the primary club.
     *
     * These are bad membership records from before the observer was added. For each:
     *   - Create or update TrooperOrganization at the primary club (active, copy identifier).
     *   - Create or update TrooperAssignment at the sub-org with is_member=true.
     *   - Soft-delete the incorrect sub-org row.
     */
    private function repairActiveSubOrgMemberships(): int
    {
        $repaired = 0;

        $sub_org_memberships = TrooperOrganization::where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->whereNull(TrooperOrganization::DELETED_AT)
            ->whereHas('organization', fn($q) => $q->where(Organization::TYPE, '!=', OrganizationType::ORGANIZATION))
            ->get();

        foreach ($sub_org_memberships as $trooper_org)
        {
            $primary_club = $trooper_org->organization->getPrimaryClub();

            $this->upsertPrimaryClubMembership($primary_club, $trooper_org);
            $this->upsertAssignment($trooper_org->organization_id, $trooper_org->trooper_id);
            $this->createOrUpdateNotificationAssignments($primary_club, $trooper_org->organization, $trooper_org->trooper_id);

            $trooper_org->delete();

            $repaired++;
        }

        return $repaired;
    }

    private function upsertPrimaryClubMembership(Organization $primary_club, TrooperOrganization $source): void
    {
        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($source->identifier))
        {
            if ($this->identifierIsAvailable($primary_club, $source->identifier, $source->trooper_id))
            {
                $update_data[TrooperOrganization::IDENTIFIER] = $source->identifier;
            }
        }

        $record = TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $source->trooper_id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $primary_club->id)
            ->first();

        if ($record)
        {
            if ($record->trashed())
            {
                $record->restore();
            }

            $record->fill($update_data)->save();

            return;
        }

        TrooperOrganization::create(array_merge(
            [
                TrooperOrganization::TROOPER_ID => $source->trooper_id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        ));
    }

    private function upsertAssignment(int $organization_id, int $trooper_id): void
    {
        $organization = Organization::findOrFail($organization_id);
        $assignment = TrooperAssignment::withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id)
            ->first();

        if ($assignment)
        {
            $this->clearConflictingMemberAssignments($organization, $trooper_id, (int) $assignment->id);

            if ($assignment->trashed())
            {
                $assignment->restore();
            }

            $assignment->is_member = true;
            $assignment->save();

            return;
        }

        $this->clearConflictingMemberAssignments($organization, $trooper_id);

        TrooperAssignment::create([
            TrooperAssignment::TROOPER_ID => $trooper_id,
            TrooperAssignment::ORGANIZATION_ID => $organization_id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    private function clearConflictingMemberAssignments(
        Organization $organization,
        int $trooper_id,
        ?int $except_assignment_id = null,
    ): int {
        $query = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereNull(TrooperAssignment::DELETED_AT)
            ->whereHas('organization', function ($query) use ($organization): void
            {
                $query->where(Organization::NODE_PATH, 'like', $organization->node_path . '%')
                    ->orWhereRaw('? LIKE CONCAT(' . Organization::NODE_PATH . ', "%")', [$organization->node_path]);
            });

        if ($except_assignment_id !== null)
        {
            $query->where(TrooperAssignment::ID, '!=', $except_assignment_id);
        }

        $ids = $query->pluck(TrooperAssignment::ID);

        if ($ids->isEmpty())
        {
            return 0;
        }

        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)
            ->update([TrooperAssignment::IS_MEMBER => false]);

        $delete_ids = TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)
            ->where(TrooperAssignment::IS_MODERATOR, false)
            ->where(TrooperAssignment::SHOULD_NOTIFY, false)
            ->pluck(TrooperAssignment::ID);

        if ($delete_ids->isNotEmpty())
        {
            TrooperAssignment::whereIn(TrooperAssignment::ID, $delete_ids)->delete();
        }

        return $ids->count();
    }

    private function createOrUpdateNotificationAssignments(
        Organization $primary_club,
        Organization $requested_org,
        int $trooper_id,
    ): void {
        foreach ($this->notificationOrganizations($primary_club, $requested_org) as $organization)
        {
            $assignment = TrooperAssignment::withTrashed()
                ->where(TrooperAssignment::TROOPER_ID, $trooper_id)
                ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
                ->first();

            if ($assignment)
            {
                if ($assignment->trashed())
                {
                    $assignment->restore();
                }

                $assignment->should_notify = true;
                $assignment->save();

                continue;
            }

            TrooperAssignment::create([
                TrooperAssignment::TROOPER_ID => $trooper_id,
                TrooperAssignment::ORGANIZATION_ID => $organization->id,
                TrooperAssignment::SHOULD_NOTIFY => true,
            ]);
        }
    }

    /**
     * @return array<int, Organization>
     */
    private function notificationOrganizations(Organization $primary_club, Organization $requested_org): array
    {
        $organizations = [];
        $organization = $requested_org;

        while ($organization !== null)
        {
            $organizations[$organization->id] = $organization;

            if ($organization->id === $primary_club->id)
            {
                break;
            }

            $organization = $organization->parent;
        }

        $organizations[$primary_club->id] = $primary_club;

        return $organizations;
    }

    private function identifierIsAvailable(Organization $primary_club, string $identifier, int $trooper_id): bool
    {
        return !TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::ORGANIZATION_ID, $primary_club->id)
            ->where(TrooperOrganization::IDENTIFIER, $identifier)
            ->where(TrooperOrganization::TROOPER_ID, '!=', $trooper_id)
            ->exists();
    }

    private function denyPendingJoinRequestsForDeniedTroopers(): int
    {
        return TrooperRequest::where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->whereHas('trooper', function ($query): void
            {
                $query->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::DENIED);
            })
            ->update([
                TrooperRequest::STATUS => TrooperRequestStatus::DENIED,
                TrooperRequest::DENIED_AT => now(),
            ]);
    }

    /**
     * State 4: is_member=true AND deleted_at IS NOT NULL
     */
    private function fixFlagNotClearedOnDelete(): int
    {
        return TrooperAssignment::withTrashed()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereNotNull(TrooperAssignment::DELETED_AT)
            ->update([TrooperAssignment::IS_MEMBER => false]);
    }

    /**
     * State 5: is_member=false AND deleted_at IS NULL AND is_moderator=false AND should_notify=false
     */
    private function fixFlagClearedButNotDeleted(): int
    {
        $ids = TrooperAssignment::where(TrooperAssignment::IS_MEMBER, false)
            ->whereNull(TrooperAssignment::DELETED_AT)
            ->where(TrooperAssignment::IS_MODERATOR, false)
            ->where(TrooperAssignment::SHOULD_NOTIFY, false)
            ->pluck(TrooperAssignment::ID);

        if ($ids->isEmpty())
        {
            return 0;
        }

        TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->delete();

        return $ids->count();
    }

    /**
     * State 6: Multiple active assignments in the same primary-club hierarchy for the same trooper.
     */
    private function fixHierarchyDuplicates(): int
    {
        $resolved = 0;

        $primary_clubs = Organization::ofTypeOrganizations()->get();

        foreach ($primary_clubs as $primary_club)
        {
            $org_ids = Organization::where(
                Organization::NODE_PATH, 'like', $primary_club->node_path . '%'
            )->pluck(Organization::ID);

            $conflicted_trooper_ids = TrooperAssignment::whereIn(TrooperAssignment::ORGANIZATION_ID, $org_ids)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->whereNull(TrooperAssignment::DELETED_AT)
                ->groupBy(TrooperAssignment::TROOPER_ID)
                ->havingRaw('COUNT(*) > 1')
                ->pluck(TrooperAssignment::TROOPER_ID);

            foreach ($conflicted_trooper_ids as $trooper_id)
            {
                $assignments = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_id)
                    ->whereIn(TrooperAssignment::ORGANIZATION_ID, $org_ids)
                    ->where(TrooperAssignment::IS_MEMBER, true)
                    ->whereNull(TrooperAssignment::DELETED_AT)
                    ->orderByDesc(TrooperAssignment::UPDATED_AT)
                    ->get();

                $stale_ids = $assignments->skip(1)->pluck(TrooperAssignment::ID);

                if ($stale_ids->isEmpty())
                {
                    continue;
                }

                TrooperAssignment::whereIn(TrooperAssignment::ID, $stale_ids)
                    ->update([TrooperAssignment::IS_MEMBER => false]);
                TrooperAssignment::whereIn(TrooperAssignment::ID, $stale_ids)->delete();

                $resolved += $stale_ids->count();
            }
        }

        return $resolved;
    }

    /**
     * State 7: Active TrooperOrganization at a primary club with no active TrooperAssignment
     * anywhere in that club's hierarchy.
     */
    private function fixOrphanedTrooperOrganizations(): int
    {
        $created = 0;

        $primary_clubs = Organization::ofTypeOrganizations()->get();

        foreach ($primary_clubs as $primary_club)
        {
            $org_ids = Organization::where(
                Organization::NODE_PATH, 'like', $primary_club->node_path . '%'
            )->pluck(Organization::ID);

            $trooper_orgs = TrooperOrganization::where(TrooperOrganization::ORGANIZATION_ID, $primary_club->id)
                ->whereNull(TrooperOrganization::DELETED_AT)
                ->get();

            foreach ($trooper_orgs as $trooper_org)
            {
                $has_active_assignment = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_org->trooper_id)
                    ->whereIn(TrooperAssignment::ORGANIZATION_ID, $org_ids)
                    ->where(TrooperAssignment::IS_MEMBER, true)
                    ->whereNull(TrooperAssignment::DELETED_AT)
                    ->exists();

                if (!$has_active_assignment)
                {
                    $this->upsertAssignment($primary_club->id, $trooper_org->trooper_id);
                    $this->createOrUpdateNotificationAssignments($primary_club, $primary_club, $trooper_org->trooper_id);
                    $created++;
                }
            }
        }

        return $created;
    }
}
