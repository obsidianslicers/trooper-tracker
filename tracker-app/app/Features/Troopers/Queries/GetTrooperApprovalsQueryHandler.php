<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving pending trooper approval requests.
 *
 * Returns troopers with PENDING status that are moderated by the specified
 * moderator/administrator. Used for displaying the approval queue.
 *
 * @implements QueryHandlerInterface<GetTrooperApprovalsQuery>
 */
readonly class GetTrooperApprovalsQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve pending approvals.
     *
     * Filters pending troopers using the pendingApprovals() and moderatedBy()
     * scopes to return only those the moderator has permission to approve.
     *
     * @param  GetTrooperApprovalsQuery  $message  The query containing the moderator
     * @return Collection<int, Trooper> Collection of pending troopers
     */
    public function __invoke(object $message): mixed
    {
        return Trooper::pendingApprovals()->moderatedBy($message->moderator)->get();
    }
}
