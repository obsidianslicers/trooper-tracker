<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve troopers who signed up for a cancelled event.
 *
 * Returns all active troopers who have a "going" status for any shift
 * in the cancelled event. These troopers should receive cancellation
 * notifications via email.
 *
 * @method static Collection<int, Trooper> call(Event $event)
 */
final class GetTroopersForEventCancelled extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Event  $event  The event for which to retrieve troopers
     */
    public function __construct(private readonly Event $event) {}

    /**
     * Execute the query to retrieve troopers for cancellation notifications.
     *
     * Process:
     * 1. Filter active troopers
     * 2. Find troopers with EventTrooper status = GOING
     * 3. Join through event_shifts to match the cancelled event
     * 4. Return collection of Trooper models
     *
     * @return Collection<int, Trooper> Active troopers who signed up for the event
     */
    public function handle(): Collection
    {
        $event_id = $this->event->id;

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
