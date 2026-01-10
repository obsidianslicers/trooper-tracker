<?php

namespace App\Services\Events;

use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Query service for retrieving active events that need to be closed.
 *
 * This service identifies all active events whose end date has passed,
 * indicating they should be marked as closed or completed.
 */
class GetEventsToCloseQuery
{
    /**
     * Retrieve all active events that have already ended.
     *
     * Queries the database for events with active status whose event_end
     * timestamp is in the past, making them eligible for closure.
     *
     * @return Collection<int, Event> Collection of Event models that need to be closed
     */
    public function __invoke(): Collection
    {
        return Event::active()
            ->where(Event::EVENT_END, '<', now())
            ->get();
    }
}
