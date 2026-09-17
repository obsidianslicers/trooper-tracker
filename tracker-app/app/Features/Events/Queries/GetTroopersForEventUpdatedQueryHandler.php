<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving troopers eligible for event update notifications.
 *
 * Queries for troopers watching the event, unioned with troopers on the
 * event's roster (signed up on one of its shifts) whose status indicates
 * intent to attend. Troopers matching both are only returned once.
 *
 * @implements QueryHandlerInterface<GetTroopersForEventUpdatedQuery>
 */
readonly class GetTroopersForEventUpdatedQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve eligible troopers for event update notifications.
     *
     * @param  GetTroopersForEventUpdatedQuery  $message  The query containing the updated event
     * @return Collection<int, Trooper> Troopers eligible for update notifications
     */
    public function __invoke(object $message): mixed
    {
        $event_id = $message->event->id;

        $statuses = array_map(
            fn (EventTrooperStatus $status) => $status->value,
            EventTrooperStatus::intentToGoArray(),
        );

        return Trooper::where(function ($query) use ($event_id, $statuses) {
            $query->whereHas('event_watches', fn ($q) => $q->where('event_id', $event_id))
                ->orWhereHas('event_troopers', function ($q) use ($event_id, $statuses) {
                    $q->whereIn(EventTrooper::STATUS, $statuses)
                        ->whereHas('event_shift', fn ($q2) => $q2->where('event_id', $event_id));
                });
        })->get()->unique('id');
    }
}
