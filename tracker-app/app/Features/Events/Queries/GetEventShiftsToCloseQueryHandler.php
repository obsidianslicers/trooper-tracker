<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\EventShift;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving active event shifts that need to be closed.
 *
 * Identifies all active event shifts whose end time has passed,
 * indicating they should be marked as closed or completed. Eager loads
 * related event, organization, and trooper data for email notifications.
 *
 * @implements QueryHandlerInterface<GetEventShiftsToCloseQuery>
 */
readonly class GetEventShiftsToCloseQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve event shifts that need closing.
     *
     * Process:
     * 1. Filter event shifts with active status
     * 2. Filter event shifts with shift_ends_at < now()
     * 3. Eager load event.organization and event_troopers.trooper
     * 4. Return collection of EventShift models
     *
     * @param GetEventShiftsToCloseQuery $message The query (no parameters)
     * @return Collection<int, EventShift> Collection of event shifts that need to be closed
     */
    public function __invoke(object $message): mixed
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
