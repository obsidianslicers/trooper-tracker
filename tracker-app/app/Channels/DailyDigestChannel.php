<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\EventNotification;
use Illuminate\Notifications\Notification;

/**
 * Preserves the existing daily digest system by creating an EventNotification record
 * with processed_at = null when a trooper has DAILY notification frequency.
 * The SendEventDailyNotificationCommand scheduler picks up these unprocessed records.
 */
final class DailyDigestChannel
{
    /**
     * Persists a pending daily-digest notification record for later processing.
     *
     * @param object $notifiable Trooper-like notifiable entity with an id.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toDailyDigest'))
        {
            return;
        }

        $data = $notification->toDailyDigest($notifiable);

        EventNotification::firstOrCreate(
            [
                EventNotification::EVENT_ID => $data['event_id'],
                EventNotification::TROOPER_ID => $notifiable->id,
            ],
            [EventNotification::PROCESSED_AT => null],
        );
    }
}
