<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
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
    use HasEventDisplayAssembler;

    /**
     * Execute the query to retrieve a single event shift for display.
     *
     * Process:
     * 1. Load event shift with ID from query
     * 2. Eager load event, trooper sign-ups, and related data
     * 3. Return EventShift model with all relationships loaded
     *
     * @param  GetEventShiftDisplayQuery  $message  The query containing the event shift ID
     * @return EventShift The event shift with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        $event_shift = EventShift::with($this->buildRelations())->findOrFail($message->event_shift->id);

        $this->assembleEventShift($event_shift, $message->trooper);

        return $event_shift;
    }

    private function buildRelations(): array
    {
        $trooper_columns = [
            Trooper::ID,
            Trooper::DISPLAY_NAME,
        ];

        return [
            'event_troopers.trooper:' . implode(',', $trooper_columns),
            'event_troopers.added_by_trooper:' . implode(',', $trooper_columns),
            'event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
            'event_guests.added_by_trooper:' . implode(',', $trooper_columns),
            'event_guests' => function ($query)
            {
                $query->orderBy(EventGuest::NAME, 'asc');
            },
        ];
    }
}
