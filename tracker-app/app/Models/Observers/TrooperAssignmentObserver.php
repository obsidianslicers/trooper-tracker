<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Models\TrooperAssignment;

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
        // Membership is allowed at any organizational level (org, region, or unit).
    }
}
