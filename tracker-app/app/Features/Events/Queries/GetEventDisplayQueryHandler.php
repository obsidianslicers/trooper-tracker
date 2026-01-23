<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;

/**
 * Handler for retrieving a single event for display.
 *
 * Returns event data with all shifts, trooper sign-ups, and related information
 * needed for rendering the event display page.
 *
 * @implements QueryHandlerInterface<GetEventDisplayQuery>
 */
readonly class GetEventDisplayQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve a single event for display.
     *
     * Process:
     * 1. Load event with ID from query
     * 2. Eager load shifts, trooper sign-ups, and related data
     * 3. Return Event model with all relationships loaded
     *
     * @param GetEventDisplayQuery $message The query containing the event ID
     * @return Event The event with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        $with = [
            'organization',
            'organizations.organization',
            'organizations' => function ($query)
            {
                $query->orderBy(Organization::NAME);
            },
            'event_shifts' => function ($query)
            {
                $query->orderBy(EventShift::SHIFT_STARTS_AT, 'asc');
            },
            'event_shifts.event_troopers.trooper',
            'event_shifts.event_troopers.added_by_trooper',
            'event_shifts.event_troopers.organization_costume.organization',
            'event_shifts.event_troopers.backup_costume.organization',
            'event_shifts.event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
        ];

        $event = Event::with($with)
            ->withShifts()
            ->findOrFail($message->event->id);

        EventDisplayAssembler::assembleEvent($event, $message->trooper);

        return $event;
    }
}