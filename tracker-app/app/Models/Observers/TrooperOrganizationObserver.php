<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\MembershipStatus;
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
     * @param  TrooperOrganization  $trooper_organization  The trooper organization instance being saved.
     *
     * @throws Exception if a trooper is assigned as a member to a non-leaf organization.
     */
    /**
     * Handle the TrooperOrganization "saved" event.
     *
     * Reconciles global trooper membership_status whenever the org-level status changes:
     * - Demote: globally ACTIVE + no remaining active orgs + has retired org → RETIRED
     * - Promote: globally RETIRED + now has an active org → ACTIVE
     * PENDING/DENIED are never touched; terminal statuses (INVALID/DEPARTED) are skipped.
     */
    public function saved(TrooperOrganization $trooper_organization): void
    {
        if (!$trooper_organization->wasChanged(TrooperOrganization::MEMBERSHIP_STATUS))
        {
            return;
        }

        $trooper = $trooper_organization->trooper;

        if ($trooper === null)
        {
            return;
        }

        if (in_array($trooper->membership_status, [MembershipStatus::INVALID, MembershipStatus::DEPARTED], true))
        {
            return;
        }

        $has_active_org = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->exists();

        if ($has_active_org && $trooper->membership_status === MembershipStatus::RETIRED)
        {
            $trooper->membership_status = MembershipStatus::ACTIVE;
            $trooper->save();

            return;
        }

        if (!$has_active_org && $trooper->membership_status === MembershipStatus::ACTIVE)
        {
            $has_retired_org = TrooperOrganization::query()
                ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
                ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::RETIRED)
                ->exists();

            if ($has_retired_org)
            {
                $trooper->membership_status = MembershipStatus::RETIRED;
                $trooper->save();
            }
        }
    }

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
