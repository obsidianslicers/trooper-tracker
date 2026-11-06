<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Models\EventShift;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve active event shifts that need to be closed.
 *
 * Returns all active event shifts whose end time has passed,
 * making them eligible for closure.
 *
 * @method static Collection<int, EventShift> call()
 */
final class GetEventShiftsToClose extends Message
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all event shifts that need closing.
     */
    public function __construct() {}

    /**
     * Execute the query to retrieve event shifts that need closing.
     *
     * Process:
     * 1. Filter event shifts with active status
     * 2. Filter event shifts with shift_ends_at < now() - 6 hours (buffer for shift signups)
     * 3. Eager load event.organization and event_troopers.trooper
     * 4. Return collection of EventShift models
     *
     * @return Collection<int, EventShift> Collection of event shifts that need to be closed
     */
    public function handle(): Collection
    {
        $with = [
            'event.organization',
            'event_troopers.trooper',
        ];

        // Buffer to ensure all shifts have ended to allow troopers to signup
        return EventShift::with($with)
            ->active()
            ->where(EventShift::SHIFT_ENDS_AT, '<', now()->subHours(6))
            ->get();
    }
}
