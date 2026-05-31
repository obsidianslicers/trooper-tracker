<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;

/**
 * Handler for updating trooper organization memberships.
 *
 * Creates or restores a TrooperAssignment record (is_member = true) for each
 * selected assignment organization, enabling the "Member Of" display on reload.
 * Soft-deletes any conflicting member assignments in the same org hierarchy
 * before creating the new one to satisfy the uniqueness constraint.
 *
 * @implements CommandHandlerInterface<UpdateTrooperMembershipsCommand>
 */
readonly class UpdateTrooperMembershipsCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  UpdateTrooperMembershipsCommand  $message
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $parent_organizations = Organization::whereIn(Organization::ID, array_keys($message->valid_data))
            ->get()
            ->keyBy(Organization::ID);

        foreach ($message->valid_data as $organization_id => $data)
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
                ->where(TrooperAssignment::TROOPER_ID, $message->trooper->id)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->where(TrooperAssignment::ORGANIZATION_ID, '!=', $assignment_id)
                ->whereHas('organization', function ($query) use ($parent_org): void {
                    $query->where(Organization::NODE_PATH, 'like', $parent_org->node_path.'%')
                        ->orWhereRaw('? LIKE CONCAT('.Organization::NODE_PATH.', "%")', [$parent_org->node_path]);
                })
                ->delete();

            $trooper_assignment = TrooperAssignment::withTrashed()
                ->where(TrooperAssignment::TROOPER_ID, $message->trooper->id)
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
                TrooperAssignment::TROOPER_ID => $message->trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $assignment_id,
                TrooperAssignment::IS_MEMBER => true,
            ]);
        }

        return null;
    }
}
