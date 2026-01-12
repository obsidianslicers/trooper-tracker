<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Query service for retrieving troopers affected by an event cancellation.
 *
 * This service identifies all active troopers who had committed to attending
 * the cancelled event by querying for troopers with a "going" status on any
 * of the event's shifts.
 *
 * @package App\Services\Events
 */
class GetTroopersForCancelledEventQuery
{
    /**
     * Return all active troopers who were marked as "going"
     * for any shift belonging to the cancelled event.
     *
     * Queries the database for active troopers who have an EventTrooper
     * relationship with a status of GOING for any shift associated with
     * the provided event. Only active troopers are returned.
     *
     * @param Event $event The cancelled event to query troopers for.
     * @return Collection Collection of Trooper models who were going to the event.
     */
    public function __invoke(Event $event): Collection
    {
        $event_id = $event->id;

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
