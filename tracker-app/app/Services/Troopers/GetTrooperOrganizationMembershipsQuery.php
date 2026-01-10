<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Support\Collection;

/**
 * Query to retrieve a trooper's organization memberships.
 *
 * Fetches all top-level organizations and enriches them with the trooper's
 * specific assignment information. For each organization, if the trooper has
 * an assignment that falls under that organization's hierarchy (based on
 * node_path matching), the assignment organization is attached.
 *
 * @package App\Services\Trooper
 */
class GetTrooperOrganizationMembershipsQuery
{
    /**
     * Retrieve organization memberships for the given trooper.
     *
     * Returns all top-level organizations ordered by name, with each organization
     * enriched with an 'assignment' property if the trooper has a member assignment
     * within that organization's hierarchy. The assignment property contains the
     * specific organization (club, region, or unit) where the trooper is assigned.
     *
     * @param Trooper $trooper The trooper whose organization memberships to retrieve.
     * @return Collection A collection of Organization models with optional 'assignment' property.
     */
    public function __invoke(Trooper $trooper): Collection
    {
        $organizations = Organization::ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get();

        $assignments = $trooper->trooper_assignments()
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
