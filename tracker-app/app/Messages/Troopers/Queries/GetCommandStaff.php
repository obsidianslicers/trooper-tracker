<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Defines criteria for retrieving active command staff troopers.
 *
 * Returns active administrators and moderators.
 *
 * @method static Collection<int, Trooper> call()
 */
final class GetCommandStaff extends Message
{
    /**
     * Initializes the command staff query.
     */
    public function __construct() {}

    /**
     * Returns active administrators and moderators ordered by display name,
     * with their top-level CS organizations.
     *
     * @return Collection<int, Trooper>
     */
    public function handle(): Collection
    {
        $roles = [
            MembershipRole::ADMINISTRATOR,
            MembershipRole::STAFF_OFFICER,
            MembershipRole::MODERATOR,
        ];

        $troopers = Trooper::active()
            ->whereIn(Trooper::MEMBERSHIP_ROLE, $roles)
            ->orderBy(Trooper::DISPLAY_NAME)
            ->get();

        // Map troopers with their CS organizations
        return $troopers->map(function (Trooper $trooper)
        {
            // Get all organizations where trooper is moderator
            $cs_org_ids = TrooperAssignment::query()
                ->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                ->where(TrooperAssignment::IS_MODERATOR, true)
                ->pluck(TrooperAssignment::ORGANIZATION_ID)
                ->toArray();

            // Get the root organization for each CS organization
            $cs_orgs = Organization::whereIn(Organization::ID, $cs_org_ids)
                ->get([Organization::ID, Organization::NAME, Organization::NODE_PATH]);

            // Extract root organizations (highest level in each tree)
            $root_org_ids = $cs_orgs->map(function (Organization $org)
            {
                // Parse node_path to get the root organization ID (first in path)
                $path_ids = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));

                return (int) reset($path_ids);
            })->unique();

            // Get the root organization names
            $cs_organizations = Organization::whereIn(Organization::ID, $root_org_ids->toArray())
                ->orderBy(Organization::NAME)
                ->get([Organization::ID, Organization::NAME]);

            $trooper->setAttribute('cs_organizations', $cs_organizations);

            return $trooper;
        });
    }
}
