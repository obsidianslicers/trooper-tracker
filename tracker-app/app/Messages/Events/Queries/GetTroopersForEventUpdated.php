<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve troopers eligible for event update notifications.
 *
 * Returns troopers watching the event plus troopers on the event's roster
 * whose sign-up status indicates intent to attend.
 *
 * @method static Collection<int, Trooper> call(Event $event)
 */
final class GetTroopersForEventUpdated extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Event  $event  The event for which to retrieve troopers
     */
    public function __construct(private readonly Event $event) {}

    /**
     * Execute the query to retrieve eligible troopers for event update notifications.
     *
     * @return Collection<int, Trooper> Troopers eligible for update notifications
     */
    public function handle(): Collection
    {
        $event_id = $this->event->id;

        $statuses = array_map(
            fn (EventTrooperStatus $status) => $status->value,
            EventTrooperStatus::intentToGoArray(),
        );

        return Trooper::where(function ($query) use ($event_id, $statuses)
        {
            $query->whereHas('event_watches', fn ($q) => $q->where('event_id', $event_id))
                ->orWhereHas('event_troopers', function ($q) use ($event_id, $statuses)
                {
                    $q->whereIn(EventTrooper::STATUS, $statuses)
                        ->whereHas('event_shift', fn ($q2) => $q2->where('event_id', $event_id));
                });
        })->get()->unique('id');
    }
}
