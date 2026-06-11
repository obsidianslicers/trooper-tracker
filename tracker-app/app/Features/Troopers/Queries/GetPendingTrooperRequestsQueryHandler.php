<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving pending club join requests.
 *
 * @implements QueryHandlerInterface<GetPendingTrooperRequestsQuery>
 */
readonly class GetPendingTrooperRequestsQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetPendingTrooperRequestsQuery  $message
     * @return Collection<int, TrooperRequest>
     */
    public function __invoke(object $message): Collection
    {
        return TrooperRequest::with(['trooper', 'organization', 'primaryOrganization'])
            ->pending()
            ->whereHas('trooper', function ($query): void {
                $query->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE);
            })
            ->forModerator($message->moderator)
            ->orderBy(TrooperRequest::CREATED_AT)
            ->get();
    }
}
