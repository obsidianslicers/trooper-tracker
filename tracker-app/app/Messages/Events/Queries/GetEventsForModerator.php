<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Query to retrieve events for moderator/administrator display.
 *
 * Filters and returns events based on moderator permissions:
 * - Administrators see all events
 * - Moderators see only events from organizations they moderate
 * - Event coordinators see only the events they created
 *
 * Supports filtering by status, organization, and search term.
 * Returns paginated results with eager-loaded relationships.
 *
 * @method static LengthAwarePaginator call(EventFilter $filter, Trooper $moderator, int $page_size = 25)
 */
final class GetEventsForModerator extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  EventFilter  $filter  The filter instance containing status, organization, and search criteria.
     * @param  Trooper  $moderator  The trooper whose permissions determine event visibility.
     * @param  int  $page_size  Number of results per page (default: 25).
     */
    public function __construct(
        private readonly EventFilter $filter,
        private readonly Trooper $moderator,
        private readonly int $page_size = 25,
    ) {}

    /**
     * Execute the query to retrieve events based on moderator permissions.
     *
     * Process:
     * 1. Eager load organization with trooper's moderator assignments
     * 2. Count related event shifts
     * 3. Apply filter criteria (status, organization, search term)
     * 4. Filter by the trooper's permissions using the managedBy scope
     * 5. Order by event_end descending
     * 6. Return paginated results with query string preserved
     *
     * @return LengthAwarePaginator Paginated collection of Event models.
     */
    public function handle(): LengthAwarePaginator
    {
        $q = Event::with([
            'organization.trooper_assignments' => function ($q)
            {
                $q->where(TrooperAssignment::TROOPER_ID, $this->moderator->id)
                    ->where(TrooperAssignment::IS_MODERATOR, true);
            },
        ]);

        $q = $q->withCount('event_shifts');

        $q = $q->filterWith($this->filter)->managedBy($this->moderator);

        $q->orderByDesc(Event::EVENT_END);

        return $q->paginate($this->page_size)->withQueryString();
    }
}
