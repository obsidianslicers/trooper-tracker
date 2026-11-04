<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve membership assignments for a trooper.
 *
 * Returns the organizational hierarchy with each organization marked
 * to indicate whether the trooper is a member of that organization
 * (is_member flag on TrooperAssignment).
 *
 * @method static Collection<int, Organization> call(Trooper $trooper)
 */
final class GetTrooperOrganizationMemberships extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose memberships to retrieve
     */
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * Execute the query to retrieve membership information.
     *
     * Process:
     * 1. Load all organizations of type 'organization'
     * 2. Get trooper's organization identifiers from pivot table
     * 3. Get trooper's member assignments (is_member = true)
     * 4. For each organization, find the most specific assignment via node_path
     *
     * @return Collection<int, Organization> Organizations with identifier and assignment data
     */
    public function handle(): Collection
    {
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        $organization_memberships = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $this->trooper->id)
            ->whereNull(TrooperOrganization::DELETED_AT)
            ->get()
            ->keyBy(TrooperOrganization::ORGANIZATION_ID);

        $assignments = $this->trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            $trooper_organization = $organization_memberships->get($organization->id);

            $assignment_org = null;
            foreach ($assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    $assignment_org = $assignment->organization;
                }
            }

            $organization->is_member = $trooper_organization !== null && $assignment_org !== null;

            if ($trooper_organization === null)
            {
                continue;
            }

            $organization->identifier = $trooper_organization->identifier;
            $organization->membership_status = $trooper_organization->membership_status;
            $organization->assignment = $assignment_org;
        }

        return $organizations;
    }
}
