<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\MembershipRole;
use App\Models\Trooper;

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
 * @implements QueryHandlerInterface<GetTroopersByRoleQuery>
 */
readonly class GetTroopersByRoleQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve administrator troopers.
     *
     * Process:
     * 1. Filter active troopers
     * 2. Filter by membership_role = ADMINISTRATOR
     * 3. Order by name
     * 4. Return collection of Trooper models
     *
     * @param GetTroopersByRoleQuery $message The query (no parameters)
     * @return \Illuminate\Support\Collection<int, Trooper> Collection of administrator troopers
     */
    public function __invoke(object $message): mixed
    {
        return Trooper::active()
            ->where(Trooper::MEMBERSHIP_ROLE, $message->membership_role)
            ->orderBy(Trooper::NAME)
            ->get();
    }
}