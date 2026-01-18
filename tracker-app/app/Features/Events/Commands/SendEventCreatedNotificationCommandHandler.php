<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Support\Facades\Mail;

/**
 * Handler for creating event notifications and sending instant emails to individual troopers.
 *
 * Creates EventNotification records for eligible troopers and immediately
 * queues emails for those with INSTANT notification preferences. Troopers
 * with DAILY preferences have unprocessed notifications created for batch
 * processing by the daily digest command.
 *
 * @implements CommandHandlerInterface<SendEventCreatedNotificationCommand>
 */
class SendEventCreatedNotificationCommandHandler
{
    /**
     * Create notification record and send instant email if applicable.
     *
     * Workflow:
     * 1. Validate trooper has a valid email address
     * 2. Create EventNotification record
     * 3. If trooper has INSTANT preference:
     *    - Mark notification as processed immediately
     *    - Queue InstantEventNotification email
     * 4. If trooper has DAILY preference:
     *    - Leave notification unprocessed for batch processing
     *
     * @param SendEventCreatedNotificationCommand $message The command containing event and trooper
     * @return null Always returns null
     */
    public function __invoke(object $message): mixed
    {
        if (!$message->trooper->emailAppearsValid())
        {
            return null;
        }

        $notification = new EventNotification();
        $notification->event_id = $message->event->id;
        $notification->trooper_id = $message->trooper->id;

        if ($message->trooper->notification_frequency === NotificationFrequency::INSTANT)
        {
            $notification->processed_at = now();
            $notification->save();

            Mail::to($message->trooper->email)
                ->queue(new InstantEventNotification($notification));
        }
        else
        {
            $notification->processed_at = null;
            $notification->save();
        }

        return null;
    }
}
