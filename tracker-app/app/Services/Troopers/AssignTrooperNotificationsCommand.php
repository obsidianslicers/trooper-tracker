<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperAssignment;

/**
 * Updates trooper notification preferences for organizations.
 *
 * This command manages the can_notify flag on TrooperAssignment records,
 * controlling whether a trooper receives event notifications from specific
 * organizations. It creates new assignments if they don't exist or updates
 * existing ones based on the provided notification preferences.
 */
class AssignTrooperNotificationsCommand
{
    /**
     * Update notification preferences for trooper's organization assignments.
     * 
     * First updates all existing assignments to disable notifications,
     * the uses the provided data to enable notifications for selected organizations.
     *
     * @param Trooper $trooper The trooper whose notification preferences are being updated
     * @param array $organizations Array keyed by organization ID, each containing:
     *                            - can_notify (bool): Whether to send notifications for this organization
     * @return void
     */
    public function __invoke(Trooper $trooper, array $organizations): void
    {
        $trooper->trooper_assignments()->update(['can_notify' => false]);

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

            $assignment->can_notify = $data['can_notify'] ?? false;

            $assignment->save();
        }
    }
}
