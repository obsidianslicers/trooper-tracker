<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve active event shifts that need to be reminded.
 *
 * Returns all active event shifts whose start time is approaching,
 * making them eligible for reminder notifications.
 *
 * @method static Collection<int, EventShift> call()
 */
final class GetEventShiftsToRemind extends Message
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all event shifts that need reminding.
     */
    public function __construct() {}

    /**
     * Execute the query to retrieve event shifts that need reminding.
     *
     * Process:
     * 1. Filter event shifts with active status
     * 2. Filter event shifts with shift_starts_at < now()
     * 3. Eager load event.organization and event_troopers.trooper
     * 4. Return collection of EventShift models
     *
     * @return Collection<int, EventShift> Collection of event shifts that need to be reminded
     */
    public function handle(): Collection
    {
        $with = [
            'event.organization',
            'event_troopers.trooper',
        ];

        return EventShift::with($with)
            ->closed()
            ->where(EventShift::SHIFT_ENDS_AT, '>=', now()->subDays(30))
            ->where(function ($query)
            {
                $query->whereNull(EventShift::LAST_NOTIFIED_AT)
                    ->orWhere(EventShift::LAST_NOTIFIED_AT, '<=', now()->subDays(3));
            })
            ->whereHas('event_troopers', function ($query)
            {
                $query->whereIn(EventTrooper::STATUS, EventTrooperStatus::intentToGoArray());
            })
            ->get();
    }
}
