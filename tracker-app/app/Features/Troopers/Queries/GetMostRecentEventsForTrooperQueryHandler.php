<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;

/**
 * Handler for retrieving the most recent attended events for a trooper.
 *
 * Returns organizations with an 'event_shift' property containing the most
 * recent EventShift where the trooper attended (status = ATTENDED).
 *
 * For each organization:
 * - Finds event shifts where the event's primary_organization_id matches
 * - Filters to shifts where the trooper has ATTENDED status
 * - Returns the most recent shift (ordered by shift_starts_at DESC)
 * - Sets event_shift property to the EventShift object or null if none found
 *
 * @implements QueryHandlerInterface<GetMostRecentEventsForTrooperQuery>
 */
readonly class GetMostRecentEventsForTrooperQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve most recent events.
     *
     * Process:
     * 1. Load all organizations of type 'organization'
     * 2. For each organization, find the most recent event shift where trooper attended
     * 3. Set 'event_shift' property on each organization (null if no matches)
     * 4. Return collection of organizations with event_shift data
     *
     * @param  GetMostRecentEventsForTrooperQuery  $message  The query containing the trooper
     * @return \Illuminate\Support\Collection<int, Organization> Organizations with event_shift property
     */
    public function __invoke(object $message): mixed
    {
        $organizations = Organization::ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get();

        foreach ($organizations as $organization)
        {
            $event_filter = function ($q) use ($organization) {
                $q->where(Event::PRIMARY_ORGANIZATION_ID, $organization->id);
            };

            $trooper_filter = function ($q) use ($message) {
                $q->where(EventTrooper::TROOPER_ID, $message->trooper->id)
                    ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
            };

            $event_shift = EventShift::with('event')
                ->whereHas('event', $event_filter)
                ->whereHas('event_troopers', $trooper_filter)
                ->orderByDesc(EventShift::SHIFT_STARTS_AT)
                ->first();

            $organization->event_shift = null;

            if ($event_shift)
            {
                $organization->event_shift = $event_shift;
            }
        }

        return $organizations;
    }
}
