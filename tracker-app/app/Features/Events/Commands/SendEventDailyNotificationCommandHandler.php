<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for sending daily event notification digest emails to troopers.
 *
 * Sends a consolidated daily digest email to troopers who have
 * opted for daily notification frequency and have pending event notifications.
 * After sending, all included event notifications are marked as processed.
 *
 * @implements CommandHandlerInterface<SendEventDailyNotificationCommand>
 */
readonly class SendEventDailyNotificationCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to send daily event notification digest.
     *
     * Process:
     * 1. Validate trooper email address
     * 2. Verify trooper has DAILY notification frequency
     * 3. Queue DailyEventNotification email with pending notifications
     * 4. Mark all event_notifications as processed
     *
     * @param  SendEventDailyNotificationCommand  $message  The command with trooper
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $trooper = $message->trooper;

        if (!$trooper->emailAppearsValid())
        {
            return null;
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

        return null;
    }
}
