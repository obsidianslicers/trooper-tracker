<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Mail;

/**
 * Command to send daily event notification digest to a trooper.
 *
 * Sends a consolidated daily digest email containing all pending event
 * notifications for a trooper who has opted for daily notifications.
 *
 * @method static void call(Trooper $trooper)
 */
final class SendEventDailyNotification extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper to send daily notifications to
     */
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    /**
     * Execute the command to send daily event notification digest.
     *
     * Process:
     * 1. Validate trooper email address
     * 2. Verify trooper has DAILY notification frequency
     * 3. Queue DailyEventNotification email with pending notifications
     * 4. Mark all event_notifications as processed
     *
     * @return null
     */
    public function handle(): void
    {
        $trooper = $this->trooper;

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
