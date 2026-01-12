<?php

namespace App\Services\Events;

use App\Enums\NotificationFrequency;
use App\Models\Event;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Support\Collection;

/**
 * Query service for retrieving troopers eligible for event creation notifications.
 *
 * This service identifies active troopers who should be notified about a new event
 * based on their notification preferences and organization assignments. Only includes
 * troopers who haven't opted out of notifications and have notification permissions
 * for the event's organization.
 *
 * @package App\Services\Events
 */
class GetTroopersForEventCreatedNotificationQuery
{
    /**
     * Retrieve all troopers eligible to receive notifications about the event.
     *
     * Queries active troopers who:
     * - Have notification frequency set to anything except NEVER
     * - Have an assignment to the event's organization with CAN_NOTIFY enabled
     *
     * @param Event $event The newly created event to notify about.
     * @return \Illuminate\Support\Collection Collection of eligible Trooper models.
     */
    public function __invoke(Event $event): Collection
    {
        $organization_id = $event->organization_id;

        return Trooper::active()
            ->where(Trooper::NOTIFICATION_FREQUENCY, '!=', NotificationFrequency::NEVER)
            ->whereHas('trooper_assignments', function ($q) use ($organization_id)
            {
                $q->where(TrooperAssignment::CAN_NOTIFY, true)
                    ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id);
            })
            ->get();
    }
}
