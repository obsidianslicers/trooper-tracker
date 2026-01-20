<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Trooper;

/**
 * Handler for retrieving troopers who signed up for a cancelled event.
 *
 * Queries for all active troopers with a "going" status for any shift
 * belonging to the cancelled event. These troopers will receive
 * cancellation notifications regardless of their notification preferences.
 *
 * @implements QueryHandlerInterface<GetEventsForDisplayQuery>
 */
readonly class GetEventsForDisplayQueryHandler implements QueryHandlerInterface
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
     * @param GetEventsForDisplayQuery $message The query containing the cancelled event
     * @return \Illuminate\Support\Collection<int, Trooper> Active troopers who signed up for the event
     */
    public function __invoke(object $message): mixed
    {
        /** @var GetEventsForDisplayQuery $message */

        $with = ['organization', 'organizations' => function ($query)
        {
            $query->wherePivot(EventOrganization::CAN_ATTEND, true);
        }];

        return Event::with($with)
            ->withShifts()
            ->upcoming()
            ->get();
    }
}