<?php

namespace App\Services\Events;

use App\Models\EventShift;
use Illuminate\Support\Collection;

/**
 * Query service for retrieving active event shifts that need to be closed.
 *
 * This service identifies all active event shifts whose end time has passed,
 * indicating they should be marked as closed or completed. Eager loads related
 * event, organization, and trooper data for email notifications.
 */
class GetEventShiftsToCloseQuery
{
    /**
     * Retrieve all active event shifts that have already ended.
     *
     * Queries the database for event shifts with active status whose shift_ends_at
     * timestamp is in the past, making them eligible for closure. Eager loads
     * event.organization and event_troopers.trooper relationships.
     *
     * @return Collection<int, EventShift> Collection of EventShift models that need to be closed
     */
    public function __invoke(): Collection
    {
        $with = [
            'event.organization',
            'event_troopers.trooper',
        ];

        return EventShift::with($with)
            ->active()
            ->where(EventShift::SHIFT_ENDS_AT, '<', now())
            ->get();
    }
}
