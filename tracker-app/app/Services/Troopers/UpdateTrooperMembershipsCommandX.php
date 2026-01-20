<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Models\Trooper;
use App\Models\TrooperAssignment;

/**
 * Command to update a trooper's organization memberships.
 *
 * Processes organization assignment data and creates or updates TrooperAssignment
 * records for the given trooper. Only processes organizations where an assignment
 * organization ID is provided in the data.
 *
 * @package App\Services\Troopers
 */
class UpdateTrooperMembershipsCommandX
{
    /**
     * Update the trooper's organization memberships.
     *
     * Iterates through the provided organizations array and creates or updates
     * TrooperAssignment records where an assignment organization ID is specified.
     * Sets the is_member flag to true for all processed assignments.
     *
     * @param Trooper $trooper The trooper whose memberships to update.
     * @param array $organizations Array of organization data with 'assignment' keys containing organization IDs.
     * @return void
     */
    public function __invoke(Trooper $trooper, array $organizations): void
    {
        foreach ($organizations as $orgId => $data)
        {
            $assignmentId = $data['assignment'] ?? null;

            if (!$assignmentId)
            {
                continue;
            }

            $assignment = $trooper->trooper_assignments
                ->firstWhere('organization_id', $assignmentId);

            if (!$assignment)
            {
                $assignment = new TrooperAssignment();
                $assignment->trooper_id = $trooper->id;
                $assignment->organization_id = $assignmentId;
            }

            $assignment->is_member = true;
            $assignment->save();
        }
    }
}
