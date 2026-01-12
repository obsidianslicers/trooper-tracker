<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Models\Trooper;
use App\Models\TrooperAssignment;

/**
 * Updates trooper membership status for organizations.
 *
 * This command manages the is_member flag on TrooperAssignment records,
 * indicating whether a trooper is an active member of specific organizations.
 * It creates new assignments if they don't exist or updates existing ones
 * based on the provided membership data.
 */
class AssignTrooperMembershipsCommand
{
    /**
     * Update membership status for trooper's organization assignments.
     *
     * @param Trooper $trooper The trooper whose membership status is being updated
     * @param array $organizations Array keyed by organization ID, each containing:
     *                            - is_member (bool): Whether the trooper is a member of this organization
     * @return void
     */
    public function __invoke(Trooper $trooper, array $organizations): void
    {
        $assignments = $trooper->trooper_assignments()->get();

        foreach ($organizations as $organization_id => $data)
        {
            $assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, $organization_id);

            if ($assignment === null)
            {
                $assignment = new TrooperAssignment();
                $assignment->trooper_id = $trooper->id;
                $assignment->organization_id = $organization_id;
            }

            $assignment->is_member = $data['is_member'] ?? false;

            $assignment->save();
        }
    }
}
