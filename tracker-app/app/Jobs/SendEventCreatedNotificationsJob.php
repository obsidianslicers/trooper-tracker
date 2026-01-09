<?php

namespace App\Jobs;

use App\Enums\NotificationFrequency;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Events\GetTroopersForEventCreatedNotificationQuery;
use App\Services\Events\SendEventCreatedNotificationsCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Creates event notifications for active troopers when a new event is posted.
 *
 * This job generates EventNotification records for all active troopers who have
 * valid email addresses and haven't already been notified about the event.
 * Troopers with instant notification preferences receive emails immediately,
 * while others have their notifications queued for batch processing.
 *
 * @package App\Jobs
 */
class SendEventCreatedNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param Event $event The event to create notifications for.
     */
    public function __construct(private readonly Event $event)
    {
        //
    }

    /**
     * Execute the job.
     *
     * Processes all active troopers and creates event notifications based on their
     * notification preferences. Troopers with instant notifications receive emails
     * immediately, while others have notifications queued for later batch processing.
     *
     * @return void
     */
    public function handle(
        GetTroopersForEventCreatedNotificationQuery $get_troopers,
        SendEventCreatedNotificationsCommand $send_emails): void
    {
        if ($this->event->create_notifications_sent_at !== null)
        {
            return;
        }

        $troopers = $get_troopers($this->event);

        $send_emails($this->event, $troopers);

        $this->event->create_notifications_sent_at = now();
        $this->event->save();
    }
}
