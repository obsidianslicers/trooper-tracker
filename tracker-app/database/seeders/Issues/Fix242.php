<?php

declare(strict_types=1);

namespace Database\Seeders\Issues;

use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Repairs bad TrooperAssignment and TrooperOrganization data left by earlier
 * versions of ApproveJoinRequestCommandHandler and RemoveTrooperMembershipCommandHandler
 * before they were updated to set is_member=false AND soft-delete atomically.
 *
 * TrooperAssignment fixes (tt_trooper_assignments):
 *
 *   1. is_member=true with deleted_at set — flag was not cleared before soft-delete.
 *      Fix: set is_member=false on those rows.
 *
 *   2. is_member=false with deleted_at null and no moderator/notify purpose — flag
 *      was cleared but the row was never soft-deleted.
 *      Fix: soft-delete those rows.
 *
 *   3. Multiple active assignments (is_member=true, not soft-deleted) in the same
 *      primary-club hierarchy for the same trooper — only one is allowed (replace rule).
 *      Fix: keep the most recently updated assignment, soft-delete the rest.
 *
 * TrooperOrganization fixes (tt_trooper_organizations):
 *
 *   4. Active TrooperOrganization at a primary club with no active TrooperAssignment
 *      anywhere in that club's hierarchy — orphaned record from old code that cleared
 *      assignments without cleaning up the membership record.
 *      Fix: soft-delete those orphaned TrooperOrganization rows.
 */
class Fix242 extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void
        {
            $counts = [
                'flag_cleared'        => $this->fixFlagNotClearedOnDelete(),
                'not_deleted'         => $this->fixFlagClearedButNotDeleted(),
                'hierarchy_dupes'     => $this->fixHierarchyDuplicates(),
                'orphaned_trooper_org' => $this->fixOrphanedTrooperOrganizations(),
            ];

            $this->command?->info('Fix242 complete:');
            $this->command?->info("  Cleared is_member flag on {$counts['flag_cleared']} soft-deleted assignment row(s).");
            $this->command?->info("  Soft-deleted {$counts['not_deleted']} cleared-but-not-deleted assignment row(s).");
            $this->command?->info("  Resolved {$counts['hierarchy_dupes']} hierarchy duplicate assignment(s).");
            $this->command?->info("  Soft-deleted {$counts['orphaned_trooper_org']} orphaned TrooperOrganization row(s).");
        });
    }

    /**
     * State 1: is_member=true AND deleted_at IS NOT NULL
     *
     * Rows were soft-deleted by old code that did not clear the flag first.
     * The row is already inactive (deleted_at is set), so we just clear the flag.
     */
    private function fixFlagNotClearedOnDelete(): int
    {
        return TrooperAssignment::withTrashed()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereNotNull(TrooperAssignment::DELETED_AT)
            ->update([TrooperAssignment::IS_MEMBER => false]);
    }

    /**
     * State 2: is_member=false AND deleted_at IS NULL AND is_moderator=false AND should_notify=false
     *
     * Rows had their flag cleared by old code that never followed up with a soft-delete.
     * Rows with is_moderator=true or should_notify=true have a separate purpose and are left alone.
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
     * State 3: multiple active assignments (is_member=true, not soft-deleted) in the
     * same primary-club hierarchy for the same trooper.
     *
     * For each primary club, finds troopers with more than one active assignment
     * anywhere in its org tree. Keeps the most recently updated record; soft-deletes
     * the rest.
     */
    private function fixHierarchyDuplicates(): int
    {
        $resolved = 0;

        $primary_clubs = Organization::ofTypeOrganizations()->get();

        foreach ($primary_clubs as $primary_club)
        {
            $org_ids = Organization::where(
                Organization::NODE_PATH, 'like', $primary_club->node_path.'%'
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
     * State 4: active TrooperOrganization at a primary club where the trooper has no
     * active TrooperAssignment (is_member=true, not soft-deleted) anywhere in that
     * club's hierarchy.
     *
     * These orphans were created when old code cleared assignments without cleaning up
     * the corresponding membership record, leaving ghost memberships that do not
     * correspond to any real placement.
     */
    private function fixOrphanedTrooperOrganizations(): int
    {
        $resolved = 0;

        $primary_clubs = Organization::ofTypeOrganizations()->get();

        foreach ($primary_clubs as $primary_club)
        {
            $org_ids = Organization::where(
                Organization::NODE_PATH, 'like', $primary_club->node_path.'%'
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
                    $trooper_org->delete();
                    $resolved++;
                }
            }
        }

        return $resolved;
    }
}
