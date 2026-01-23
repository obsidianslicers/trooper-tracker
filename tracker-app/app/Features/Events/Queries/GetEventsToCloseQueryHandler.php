<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving active events that need to be closed.
 *
 * Identifies all active events whose end date has passed,
 * indicating they should be marked as closed or completed.
 *
 * @implements QueryHandlerInterface<GetEventsToCloseQuery>
 */
readonly class GetEventsToCloseQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve events that need closing.
     *
     * Process:
     * 1. Filter events with active status
     * 2. Filter events with event_end < now()
     * 3. Return collection of Event models
     *
     * @param GetEventsToCloseQuery $message The query (no parameters)
     * @return Collection<int, Event> Collection of events that need to be closed
     */
    public function __invoke(object $message): mixed
    {
        return Event::active()
            ->where(Event::EVENT_END, '<', now())
            ->get();
    }
}
