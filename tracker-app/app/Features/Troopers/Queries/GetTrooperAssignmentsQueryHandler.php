<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;

/**
 * Handler for retrieving trooper organization assignments.
 *
 * Returns organizations with an 'assignment' property indicating which
 * specific organization/region/unit the trooper is a member of within
 * that organization's hierarchy.
 *
 * Uses node_path matching to determine the most specific assignment
 * (e.g., if trooper is in a unit, marks the parent organization with that unit).
 *
 * @implements QueryHandlerInterface<GetTrooperAssignmentsQuery>
 */
readonly class GetTrooperAssignmentsQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve assignment information.
     *
     * Process:
     * 1. Load all organizations of type 'organization'
     * 2. Load trooper's member assignments (is_member = true)
     * 3. For each organization, find matching assignment via node_path
     * 4. Set 'assignment' property to the assigned organization object
     *
     * @param GetTrooperAssignmentsQuery $message The query containing the trooper
     * @return \Illuminate\Support\Collection<int, Organization> Organizations with assignment data
     */
    public function __invoke(object $message): mixed
    {
        $organizations = Organization::ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get();

        $assignments = $message->trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
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