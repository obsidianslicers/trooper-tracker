<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving clubs available for a trooper to request membership in.
 *
 * @implements QueryHandlerInterface<GetAvailableClubsQuery>
 */
readonly class GetAvailableClubsQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetAvailableClubsQuery  $message
     * @return Collection<int, Organization>
     */
    public function __invoke(object $message): Collection
    {
        $trooper_id = $message->trooper->id;

        // Only exclude orgs the trooper is already an active member of directly.
        // Other orgs in the same family remain visible so they can request a different level.
        $active_org_ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID);

        $query = Organization::query();

        if ($active_org_ids->isNotEmpty())
        {
            $query->whereNotIn(Organization::ID, $active_org_ids);
        }

        if ($message->trooper->is_visitor)
        {
            $query->where(Organization::DEPTH, 0);
        }

        return $query->orderBy(Organization::SEQUENCE)->get();
    }
}
