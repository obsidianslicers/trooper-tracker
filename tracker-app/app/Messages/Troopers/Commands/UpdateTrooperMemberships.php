<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;

/**
 * Command to update a trooper's organization memberships.
 *
 * Creates or updates TrooperAssignment records to set which organizations,
 * regions, or units the trooper is a member of. Sets the is_member flag
 * to true for all specified assignments.
 *
 * @method static void call(Trooper $trooper, array $valid_data)
 */
final class UpdateTrooperMemberships extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper whose memberships to update
     * @param  array<int, array{assignment: int|null}>  $valid_data  Organization IDs mapped to assignment data
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $valid_data,
    ) {}

    /**
     * @return null
     */
    public function handle(): void
    {
        $parent_organizations = Organization::whereIn(Organization::ID, array_keys($this->valid_data))
            ->get()
            ->keyBy(Organization::ID);

        foreach ($this->valid_data as $organization_id => $data)
        {
            $assignment_id = $data['assignment'] ?? null;

            if (!$assignment_id)
            {
                continue;
            }

            $parent_org = $parent_organizations->get($organization_id);

            if (!$parent_org)
            {
                continue;
            }

            // Soft-delete conflicting member assignments in the same hierarchy so the
            // observer's uniqueness check passes when we create/restore the new record.
            TrooperAssignment::query()
                ->where(TrooperAssignment::TROOPER_ID, $this->trooper->id)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->where(TrooperAssignment::ORGANIZATION_ID, '!=', $assignment_id)
                ->whereHas(
                    'organization',
                    fn ($query) => $query->onBranchOfNodePath($parent_org->node_path)
                )
                ->delete();

            $trooper_assignment = TrooperAssignment::withTrashed()
                ->where(TrooperAssignment::TROOPER_ID, $this->trooper->id)
                ->where(TrooperAssignment::ORGANIZATION_ID, $assignment_id)
                ->first();

            if ($trooper_assignment)
            {
                if ($trooper_assignment->trashed())
                {
                    $trooper_assignment->restore();
                }

                $trooper_assignment->is_member = true;
                $trooper_assignment->save();

                continue;
            }

            TrooperAssignment::create([
                TrooperAssignment::TROOPER_ID => $this->trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $assignment_id,
                TrooperAssignment::IS_MEMBER => true,
            ]);
        }
    }
}
