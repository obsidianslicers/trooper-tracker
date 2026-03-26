<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for retrieving events based on moderator permissions.
 *
 * Filters events according to trooper role:
 * - Administrators receive all events matching filters
 * - Moderators receive only events from organizations they moderate
 *
 * Applies EventFilter criteria (status, organization, search term).
 * Eager loads organization relationships and event shift counts.
 * Returns paginated results ordered by event end date (descending).
 *
 * @implements QueryHandlerInterface<GetEventsForModeratorQuery>
 */
readonly class GetEventsForModeratorQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve events based on moderator permissions.
     *
     * Process:
     * 1. Eager load organization with trooper's moderator assignments
     * 2. Count related event shifts
     * 3. Apply filter criteria (status, organization, search term)
     * 4. Filter by moderator permissions using moderatedBy scope
     * 5. Order by event_end descending
     * 6. Return paginated results with query string preserved
     *
     * @param  GetEventsForModeratorQuery  $message  The query containing filter, moderator, and page_size.
     * @return LengthAwarePaginator Paginated collection of Event models.
     */
    public function __invoke(object $message): mixed
    {
        $q = Event::with([
            'organization.trooper_assignments' => function ($q) use ($message) {
                $q->where(TrooperAssignment::TROOPER_ID, $message->moderator->id)
                    ->where(TrooperAssignment::IS_MODERATOR, true);
            },
        ]);

        $q = $q->withCount('event_shifts');

        $q = $q->filterWith($message->filter)->moderatedBy($message->moderator);

        $q->orderByDesc(Event::EVENT_END);

        return $q->paginate($message->page_size)->withQueryString();
    }
}
