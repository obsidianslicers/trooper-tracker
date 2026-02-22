<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving troopers who need daily notification digests.
 *
 * Identifies active troopers who have opted for daily notification
 * frequency and have unprocessed event notifications pending. Results include
 * eager-loaded event_notifications for efficient digest generation.
 *
 * @implements QueryHandlerInterface<GetTroopersForDailyEventNotificationsQuery>
 */
readonly class GetTroopersForDailyEventNotificationsQueryHandler implements QueryHandlerInterface
{
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
     * @param  GetTroopersForDailyEventNotificationsQuery  $message  The query (no parameters)
     * @return Collection<int, Trooper> Collection of troopers needing daily notifications
     */
    public function __invoke(object $message): mixed
    {
        $with = [
            'event_notifications' => function ($q) {
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
