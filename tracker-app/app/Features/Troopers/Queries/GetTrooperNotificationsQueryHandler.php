<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;

/**
 * Handler for retrieving trooper notification preferences.
 *
 * Returns the full organization hierarchy (organizations → regions → units)
 * with each node marked with a 'selected' property indicating whether
 * the trooper has notifications enabled (can_notify = true) for that organization.
 *
 * This data structure is used for displaying hierarchical notification
 * preference forms where troopers can enable/disable notifications per organization.
 *
 * @implements QueryHandlerInterface<GetTrooperNotificationsQuery>
 */
readonly class GetTrooperNotificationsQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve notification preferences.
     *
     * Process:
     * 1. Load full organization hierarchy using fullyLoaded() scope
     * 2. Get trooper's assignments where can_notify = true
     * 3. Mark each organization/region/unit as 'selected' if assignment exists
     *
     * @param GetTrooperNotificationsQuery $message The query containing the trooper
     * @return \Illuminate\Support\Collection<int, Organization> Organizations with selected flags
     */
    public function __invoke(object $message): mixed
    {
        $organizations = Organization::fullyLoaded()->get();

        $trooper_assignments = $message->trooper->trooper_assignments()
            ->where(TrooperAssignment::CAN_NOTIFY, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->toArray();

        foreach ($organizations as $organization)
        {
            $organization->selected = in_array($organization->id, $trooper_assignments);

            foreach ($organization->organizations as $region)
            {
                $region->selected = in_array($region->id, $trooper_assignments);

                foreach ($region->organizations as $unit)
                {
                    $unit->selected = in_array($unit->id, $trooper_assignments);
                }
            }
        }

        return $organizations;
    }
}