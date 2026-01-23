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
 * Handler for retrieving event shift display data.
 *
 * Returns an event shift with all related data needed for rendering
 * the shift display, including trooper sign-ups and event details.
 *
 * @implements QueryHandlerInterface<GetEventShiftDisplayQuery>
 */
readonly class GetEventShiftDisplayQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve a single event shift for display.
     *
     * Process:
     * 1. Load event shift with ID from query
     * 2. Eager load event, trooper sign-ups, and related data
     * 3. Return EventShift model with all relationships loaded
     *
     * @param GetEventShiftDisplayQuery $message The query containing the event shift ID
     * @return EventShift The event shift with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        $with = [
            'event_troopers.trooper',
            'event_troopers.added_by_trooper',
            'event_troopers.organization_costume.organization',
            'event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
        ];

        $event_shift = EventShift::with($with)->findOrFail($message->event_shift->id);

        EventDisplayAssembler::assembleEventShift($event_shift, $message->trooper);

        return $event_shift;
    }
}