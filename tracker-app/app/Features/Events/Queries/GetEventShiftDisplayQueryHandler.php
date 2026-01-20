<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;

/**
 * Handler for retrieving troopers who signed up for a cancelled event.
 *
 * Queries for all active troopers with a "going" status for any shift
 * belonging to the cancelled event. These troopers will receive
 * cancellation notifications regardless of their notification preferences.
 *
 * @implements QueryHandlerInterface<GetEventShiftDisplayQuery>
 */
readonly class GetEventShiftDisplayQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve troopers for cancellation notifications.
     *
     * Process:
     * 1. Filter active troopers
     * 2. Find troopers with EventTrooper status = GOING
     * 3. Join through event_shifts to match the cancelled event
     * 4. Return collection of Trooper models
     *
     * @param GetEventShiftDisplayQuery $message The query containing the cancelled event
     * @return EventShift The event shift with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        /** @var GetEventShiftDisplayQuery $message */

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