<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\JoinRequest;
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
     * @return Collection<int, JoinRequest>
     */
    public function __invoke(object $message): Collection
    {
        return JoinRequest::with(['trooper', 'organization', 'primaryOrganization'])
            ->pending()
            ->forModerator($message->moderator)
            ->orderBy(JoinRequest::CREATED_AT)
            ->get();
    }
}
