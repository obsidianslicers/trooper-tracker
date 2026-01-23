<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;

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
     * @param GetTrooperMembershipsQuery $message The query containing the trooper
     * @return \Illuminate\Support\Collection<int, Organization> Organizations with identifier and assignment data
     */
    public function __invoke(object $message): mixed
    {
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        $organization_memberships = $message->trooper->organizations()->pluck('tt_trooper_organizations.identifier', 'tt_organizations.id')->toArray();

        $assignments = $message->trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            if (isset($organization_memberships[$organization->id]) === false)
            {
                continue;
            }

            $organization->identifier = $organization_memberships[$organization->id];

            foreach ($assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    $organization->assignment = $assignment->organization;
                }
            }
        }

        return $organizations;
    }
}