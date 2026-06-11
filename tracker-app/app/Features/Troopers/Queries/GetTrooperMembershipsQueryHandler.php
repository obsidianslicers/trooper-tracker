<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving trooper membership assignments.
 *
 * Returns all organizations with added properties:
 * - 'identifier': Trooper's identifier for that organization (from pivot table)
 * - 'assignment': The assigned organization object (via node_path matching)
 *
 * This data structure is used for membership assignment forms where troopers
 * set their TK numbers and other organization identifiers.
 *
 * @implements QueryHandlerInterface<GetTrooperMembershipsQuery>
 */
readonly class GetTrooperMembershipsQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve membership information.
     *
     * Process:
     * 1. Load all organizations of type 'organization'
     * 2. Get trooper's organization identifiers from pivot table
     * 3. Get trooper's member assignments (is_member = true)
     * 4. For each organization, find the most specific assignment via node_path
     *
     * @param  GetTrooperMembershipsQuery  $message  The query containing the trooper
     * @return Collection<int, Organization> Organizations with identifier and assignment data
     */
    public function __invoke(object $message): Collection
    {
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        $organization_memberships = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $message->trooper->id)
            ->whereNull(TrooperOrganization::DELETED_AT)
            ->get()
            ->keyBy(TrooperOrganization::ORGANIZATION_ID);

        $assignments = $message->trooper->trooper_assignments()
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
