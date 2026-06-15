<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Features\Troopers\Support\OrganizationIdentifierAvailability;
use App\Models\Organization;
use App\Models\TrooperOrganization;
use Exception;

/**
 * Handles lifecycle events for the TrooperOrganization model.
 */
class TrooperOrganizationObserver
{
    /**
     * Handle the TrooperOrganization "saving" event.
     *
     * Enforces the business rule that a trooper can only be a "member" of an
     * organization that is a leaf node (has no children).
     *
     * @param TrooperOrganization $trooper_organization The trooper organization instance being saved.
     * @return void
     * @throws Exception if a trooper is assigned as a member to a non-leaf organization.
     */
    public function saving(TrooperOrganization $trooper_organization): void
    {
        $primary_club = $trooper_organization->organization->getPrimaryClub();

        if ($trooper_organization->organization_id != $primary_club->id)
        {
            throw new Exception('Trooper can only be a member at top-level organizations.');
        }

        app(OrganizationIdentifierAvailability::class)->ensureAvailable(
            $primary_club,
            $trooper_organization->identifier,
            $trooper_organization->trooper_id,
            ignore_trooper_organization_id: $trooper_organization->id
        );
    }
}
