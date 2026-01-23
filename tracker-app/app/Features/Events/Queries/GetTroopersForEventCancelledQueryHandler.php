<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;

/**
 * Handler for retrieving troopers who signed up for a cancelled event.
 *
 * Queries for all active troopers with a "going" status for any shift
 * belonging to the cancelled event. These troopers will receive
 * cancellation notifications regardless of their notification preferences.
 *
 * @implements QueryHandlerInterface<GetTroopersForEventCancelledQuery>
 */
readonly class GetTroopersForEventCancelledQueryHandler implements QueryHandlerInterface
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
     * @param GetTroopersForEventCancelledQuery $message The query containing the cancelled event
     * @return \Illuminate\Support\Collection<int, Trooper> Active troopers who signed up for the event
     */
    public function __invoke(object $message): mixed
    {
        $event_id = $message->event->id;

        $filter = function ($q) use ($event_id)
        {
            $q->where(EventTrooper::STATUS, EventTrooperStatus::GOING)
                ->whereHas('event_shift', function ($q) use ($event_id)
                {
                    $q->where(EventShift::EVENT_ID, $event_id);
                });
        };

        return Trooper::active()
            ->whereHas('event_troopers', $filter)
            ->get();
    }
}