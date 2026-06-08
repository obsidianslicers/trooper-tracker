<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving active event shifts that need to be reminded.
 *
 * Identifies all active event shifts whose start time is approaching,
 * indicating they should be reminded. Eager loads
 * related event, organization, and trooper data for email notifications.
 *
 * @implements QueryHandlerInterface<GetEventShiftsToRemindQuery>
 */
readonly class GetEventShiftsToRemindQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve event shifts that need reminding.
     *
     * Process:
     * 1. Filter event shifts with active status
     * 2. Filter event shifts with shift_starts_at < now()
     * 3. Eager load event.organization and event_troopers.trooper
     * 4. Return collection of EventShift models
     *
     * @param  GetEventShiftsToRemindQuery  $message  The query (no parameters)
     * @return Collection<int, EventShift> Collection of event shifts that need to be reminded
     */
    public function __invoke(object $message): mixed
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
