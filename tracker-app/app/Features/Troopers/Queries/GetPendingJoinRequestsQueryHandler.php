<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving pending club join requests.
 *
 * @implements QueryHandlerInterface<GetPendingJoinRequestsQuery>
 */
readonly class GetPendingJoinRequestsQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetPendingJoinRequestsQuery  $message
     * @return Collection<int, TrooperOrganization>
     */
    public function __invoke(object $message): Collection
    {
        return TrooperOrganization::with(['trooper', 'organization'])
            ->pending()
            ->whereHas('trooper', fn ($q) => $q->where(Trooper::MEMBERSHIP_STATUS, '!=', MembershipStatus::PENDING))
            ->forModerator($message->moderator)
            ->orderBy(TrooperOrganization::CREATED_AT)
            ->get();
    }
}
