<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Models\Event;
use App\Models\EventShift;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve active events that need to be closed.
 *
 * Returns all active events whose end date has passed,
 * making them eligible for closure.
 *
 * @method static Collection<int, Event> call()
 */
final class GetEventsToClose extends Message
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all events that need closing.
     */
    public function __construct() {}

    /**
     * Execute the query to retrieve events that need closing.
     *
     * Process:
     * 1. Filter events with active status
     * 2. Filter events with event_end < now() - 6 hours (buffer for shift signups)
     * 3. Return collection of Event models
     *
     * @return Collection<int, Event> Collection of events that need to be closed
     */
    public function handle(): Collection
    {
        // Buffer to ensure all shifts have ended to allow troopers to signup
        $cutoff = now()->subHours(6);

        return Event::active()
            ->where(function ($query) use ($cutoff): void
            {
                $query->where(function ($query) use ($cutoff): void
                {
                    $query->whereHas('event_shifts')
                        ->whereDoesntHave('event_shifts', function ($query) use ($cutoff): void
                        {
                            $query->where(EventShift::SHIFT_ENDS_AT, '>=', $cutoff);
                        });
                })
                    ->orWhere(function ($query) use ($cutoff): void
                    {
                        $query->whereDoesntHave('event_shifts')
                            ->where(Event::EVENT_END, '<', $cutoff);
                    });
            })
            ->get();
    }
}
