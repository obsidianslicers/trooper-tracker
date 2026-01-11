<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperOrganization;

/**
 * Assigns organization-specific member identifiers to a trooper.
 *
 * This command creates TrooperOrganization records linking a trooper to
 * organizations with their member identifiers (e.g., TK-12345, SL-67890).
 * Only processes organizations that are selected and have a valid identifier.
 */
class AssignTrooperIdentifiersCommand
{
    /**
     * Create trooper organization assignments with member identifiers.
     *
     * @param Trooper $trooper The trooper to assign identifiers for
     * @param array $organizations Array keyed by organization ID, each containing:
     *                            - identifier (string): The member identifier for that organization
     * @return void
     */
    public function __invoke(Trooper $trooper, array $organizations): void
    {
        $identifiers = $trooper->organizations()->using(TrooperOrganization::class)->get();

        foreach ($organizations as $organization_id => $data)
        {
            if (empty($data['identifier']))
            {
                continue;
            }

            $id = trim($data['identifier']);

            $identifier = $identifiers->firstWhere(TrooperOrganization::ORGANIZATION_ID, $organization_id);

            if ($identifier === null)
            {
                $trooper_organization = new TrooperOrganization();
                $trooper_organization->trooper_id = $trooper->id;
                $trooper_organization->organization_id = $organization_id;
                $trooper_organization->membership_status = MembershipStatus::ACTIVE;
                $trooper_organization->identifier = $id;
                $trooper_organization->save();
            }
            else
            {
                $identifier->pivot->identifier = $id;
                $identifier->pivot->save();
            }
        }
    }
}
