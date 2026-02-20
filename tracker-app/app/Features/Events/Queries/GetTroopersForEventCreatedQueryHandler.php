<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use App\Models\TrooperAssignment;

/**
 * Handler for retrieving troopers eligible for event creation notifications.
 *
 * Queries for active troopers who have opted in to receive notifications
 * for the organization hosting the event. Respects trooper notification
 * preferences (NEVER, INSTANT, DAILY) and per-organization should_notify settings.
 *
 * @implements QueryHandlerInterface<GetTroopersForEventCreatedQuery>
 */
readonly class GetTroopersForEventCreatedQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve eligible troopers for event notifications.
     *
     * Process:
     * 1. Filter active troopers
     * 2. Exclude troopers with notification_frequency = NEVER
     * 3. Filter by trooper_assignments where should_notify = true
     * 4. Match organization_id to the event's organization
     * 5. Return collection of Trooper models
     *
     * @param  GetTroopersForEventCreatedQuery  $message  The query containing the new event
     * @return \Illuminate\Support\Collection<int, Trooper> Active troopers eligible for notifications
     */
    public function __invoke(object $message): mixed
    {
        $organization_id = $message->event->organization_id;

        return Trooper::active()
            ->where(Trooper::NOTIFICATION_FREQUENCY, '!=', NotificationFrequency::NEVER)
            ->whereHas('trooper_assignments', function ($q) use ($organization_id) {
                $q->where(TrooperAssignment::SHOULD_NOTIFY, true)
                    ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id);
            })
            ->get();
    }
}
