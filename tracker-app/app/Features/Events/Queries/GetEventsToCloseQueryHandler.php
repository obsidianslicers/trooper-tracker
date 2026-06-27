<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\EventShift;
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
     * 2. Filter events with event_end < now() - 6 hours (buffer for shift signups)
     * 3. Return collection of Event models
     *
     * @param  GetEventsToCloseQuery  $message  The query (no parameters)
     * @return Collection<int, Event> Collection of events that need to be closed
     */
    public function __invoke(object $message): mixed
    {
        // Buffer to ensure all shifts have ended to allow troopers to signup
        $cutoff = now()->subHours(6);

        return Event::active()
            ->where(function ($query) use ($cutoff): void {
                $query->where(function ($query) use ($cutoff): void {
                    $query->whereHas('event_shifts')
                        ->whereDoesntHave('event_shifts', function ($query) use ($cutoff): void {
                            $query->where(EventShift::SHIFT_ENDS_AT, '>=', $cutoff);
                        });
                })
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereDoesntHave('event_shifts')
                            ->where(Event::EVENT_END, '<', $cutoff);
                    });
            })
            ->get();
    }
}
