<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Messages\Concerns\HasEventDisplayAssembler;
use App\Models\Event;
use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve all events for list display.
 *
 * Returns all events with associated organizations and shifts,
 * ordered appropriately for list/calendar views.
 *
 * @method static Collection<int, Event> call()
 */
final class GetEventsForDisplay extends Message
{
    use HasEventDisplayAssembler;

    /**
     * Create a new query instance.
     *
     * No parameters required - returns all events.
     */
    public function __construct() {}

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
     * @return Collection<int, Event> Collection of upcoming open events
     */
    public function handle(): Collection
    {
        $this->bootHasEventDisplayAssembler();

        $relations = [
            'organization',
            'organizations' => function ($query)
            {
                $query->orderBy(Organization::NAME);
            },
        ];

        $events = Event::with($relations)
            ->withUpcomingShifts()
            ->upcoming()
            ->get();

        $this->assembleEventOrganizations($events);

        return $events;
    }
}
