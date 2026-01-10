<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use App\Models\Trooper;
use Illuminate\Support\Facades\Mail;

/**
 * Service for sending daily event notification digest emails to troopers.
 *
 * This service sends a consolidated daily digest email to troopers who have
 * opted for daily notification frequency and have pending event notifications.
 * After sending, all included event notifications are marked as processed.
 */
class SendEventDailyNotificationCommand
{
    /**
     * Send daily event notification digest to a trooper.
     *
     * Queues a DailyEventNotification email containing all pending event
     * notifications for troopers with DAILY notification frequency preference.
     * After queueing the email, marks all included event_notifications as
     * processed by setting their processed_at timestamp.
     *
     * @param Trooper $trooper The trooper to send daily notifications to
     * @return void
     */
    public function __invoke(Trooper $trooper): void
    {
        if (!$trooper->emailAppearsValid())
        {
            return;
        }

        if ($trooper->notification_frequency === NotificationFrequency::DAILY)
        {
            Mail::to($trooper->email)->queue(new DailyEventNotification($trooper->event_notifications));

            foreach ($trooper->event_notifications as $event_notification)
            {
                $event_notification->processed_at = now();
                $event_notification->save();
            }
        }
    }
}