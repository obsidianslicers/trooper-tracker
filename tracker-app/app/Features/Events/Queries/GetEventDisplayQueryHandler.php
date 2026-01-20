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
 * @implements QueryHandlerInterface<GetEventDisplayQuery>
 */
readonly class GetEventDisplayQueryHandler implements QueryHandlerInterface
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
     * @param GetEventDisplayQuery $message The query containing the cancelled event
     * @return Event The event with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        /** @var GetEventDisplayQuery $message */

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