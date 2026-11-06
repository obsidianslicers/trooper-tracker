<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve troopers who need daily notification digests.
 *
 * Returns active troopers who have opted for daily notification
 * frequency and have unprocessed event notifications pending.
 *
 * @method static Collection<int, Trooper> call()
 */
final class GetTroopersForDailyEventNotifications extends Message
{
    /**
     * Create a new query instance.
     *
     * No parameters required - returns all troopers needing daily notifications.
     */
    public function __construct() {}

    /**
     * Execute the query to retrieve troopers needing daily notifications.
     *
     * Process:
     * 1. Filter active troopers
     * 2. Filter by notification_frequency = DAILY
     * 3. Filter by having at least one unprocessed event_notification
     * 4. Eager load unprocessed event_notifications
     * 5. Return collection of Trooper models
     *
     * @return Collection<int, Trooper> Collection of troopers needing daily notifications
     */
    public function handle(): Collection
    {
        $with = [
            'event_notifications' => function ($q)
            {
                $q->whereNull('processed_at');
            },
        ];

        return Trooper::active()
            ->with($with)
            ->where(Trooper::NOTIFICATION_FREQUENCY, NotificationFrequency::DAILY)
            ->whereHas('event_notifications', fn ($q) => $q->whereNull('processed_at')
            )
            ->get();
    }
}
