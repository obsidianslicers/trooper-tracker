<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Query service for retrieving troopers who need daily notification digests.
 *
 * This service identifies active troopers who have opted for daily notification
 * frequency and have unprocessed event notifications pending. Results include
 * eager-loaded event_notifications for efficient digest generation.
 */
class GetTroopersForDailyEventNotificationsQuery
{
    /**
     * Retrieve all active troopers with daily notification preference and pending notifications.
     *
     * Queries the database for troopers who:
     * - Are active (not retired or pending)
     * - Have notification_frequency set to DAILY
     * - Have at least one event_notification with null processed_at
     *
     * Eager loads the event_notifications relationship filtered to only
     * include unprocessed notifications.
     *
     * @return Collection<int, Trooper> Collection of Trooper models needing daily notifications
     */
    public function __invoke(): Collection
    {
        $with = [
            'event_notifications' => function ($q)
            {
                $q->whereNull('processed_at');
            }
        ];

        return Trooper::active()
            ->with($with)
            ->where(Trooper::NOTIFICATION_FREQUENCY, NotificationFrequency::DAILY)
            ->whereHas('event_notifications', fn($q) =>
                $q->whereNull('processed_at')
            )
            ->get();
    }
}