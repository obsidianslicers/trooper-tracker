<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving events for display.
 *
 * Returns a filtered collection of events based on status, date range,
 * and organization criteria. Used for event list pages and calendars.
 *
 * @implements QueryHandlerInterface<GetEventsForDisplayQuery>
 */
readonly class GetEventsForDisplayQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    /**
     * Execute the query to retrieve upcoming events for display.
     *
     * Process:
     * 1. Filter events with status = OPEN
     * 2. Filter events with event_date >= today
     * 3. Order by event_date ascending
     * 4. Eager load shifts and related data
     * 5. Return collection of Event models
     *
     * @param  GetEventsForDisplayQuery  $message  The query (no parameters)
     * @return Collection<int, Event> Collection of upcoming open events
     */
    public function __invoke(object $message): mixed
    {
        $relations = [
            'organization',
            'organizations' => function ($query) {
                $query->orderBy(Organization::NAME);
            },
        ];

        $events = Event::with($relations)
            ->withShifts()
            ->upcoming()
            ->get();

        $this->assembleEventOrganizations($events);

        return $events;
    }
}
