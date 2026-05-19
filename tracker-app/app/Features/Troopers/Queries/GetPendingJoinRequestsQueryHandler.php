<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
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
    public function __invoke(object $message): mixed
    {
        return TrooperOrganization::with(['trooper', 'organization'])
            ->pending()
            ->forModerator($message->moderator)
            ->orderBy(TrooperOrganization::CREATED_AT)
            ->get();
    }
}
