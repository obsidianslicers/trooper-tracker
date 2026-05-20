<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Models\Organization;
use App\Models\TrooperAssignment;
use Exception;

/**
 * Handles lifecycle events for the TrooperAssignment model.
 */
class TrooperAssignmentObserver
{
    /**
     * Handle the TrooperAssignment "saving" event.
     *
     * Enforces the business rule that a trooper can only be a "member" of an
     * organization that is a leaf node (has no children).
     *
     * @param TrooperAssignment $trooper_assignment The trooper assignment instance being saved.
     * @return void
     * @throws Exception if a trooper is assigned as a member to a non-leaf organization.
     */
    public function saving(TrooperAssignment $trooper_assignment): void
    {
        if ($trooper_assignment->trooper->is_visitor)
        {
            $organization = $trooper_assignment->organization;

            if ($organization && $organization->depth > 1)
            {
                throw new Exception('Visitors can only join top-level organizations.');
            }
        }

        // Membership is allowed at any organizational level (org, region, or unit).

        if (!$trooper_assignment->is_member)
        {
            return;
        }

        $organization = $trooper_assignment->organization;
        $node_path = $organization->node_path;

        $conflict_query = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $trooper_assignment->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->where(TrooperAssignment::ID, '!=', (int) $trooper_assignment->getKey())
            ->whereHas('organization', function ($query) use ($node_path): void
            {
                $query->where(Organization::NODE_PATH, 'like', $node_path . '%')
                    ->orWhereRaw('? LIKE CONCAT(' . Organization::NODE_PATH . ', "%")', [$node_path]);
            });

        if ($conflict_query->exists())
        {
            throw new Exception(
                'Trooper can only have one primary membership in the same organization hierarchy.'
            );
        }
    }
}
