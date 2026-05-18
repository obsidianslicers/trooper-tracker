<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\JoinRequestStatus;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperJoinRequest;
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
    public function __invoke(object $message): mixed
    {
        $trooper_id = $message->trooper->id;

        return Organization::ofTypeUnits()
            ->with('parent')
            ->whereDoesntHave('trooper_assignments', function ($q) use ($trooper_id)
            {
                $q->where(TrooperAssignment::TROOPER_ID, $trooper_id)
                  ->where(TrooperAssignment::IS_MEMBER, true);
            })
            ->whereDoesntHave('trooper_join_requests', function ($q) use ($trooper_id)
            {
                $q->where(TrooperJoinRequest::TROOPER_ID, $trooper_id)
                  ->where(TrooperJoinRequest::STATUS, JoinRequestStatus::PENDING);
            })
            ->orderBy(Organization::SEQUENCE)
            ->get();
    }
}
