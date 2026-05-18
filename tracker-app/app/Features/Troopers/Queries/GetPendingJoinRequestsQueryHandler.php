<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\TrooperJoinRequest;
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
     * @return Collection<int, TrooperJoinRequest>
     */
    public function __invoke(object $message): mixed
    {
        return TrooperJoinRequest::with(['trooper', 'organization'])
            ->pending()
            ->forModerator($message->moderator)
            ->orderBy(TrooperJoinRequest::CREATED_AT)
            ->get();
    }
}
