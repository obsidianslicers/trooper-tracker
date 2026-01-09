<?php

namespace App\Jobs;

use App\Enums\NotificationFrequency;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
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
    public function handle(): void
    {
        if ($this->event->create_notifications_sent_at !== null)
        {
            return;
        }

        //  TODO CROSS CHECK THIS WITH TROOPER_NOTIFICATIONS OF HOSTING ORGANIZATION
        $existing_notifications = $this->event->event_notifications
            ->pluck(EventNotification::TROOPER_ID)
            ->flip()
            ->all();

        $troopers = Trooper::active()
            ->where(Trooper::NOTIFICATION_FREQUENCY, '!=', NotificationFrequency::NEVER)
            ->get();

        foreach ($troopers as $trooper)
        {
            if ($trooper->emailAppearsValid() && !isset($existing_notifications[$trooper->id]))
            {
                $event_notification = new EventNotification();

                $event_notification->event_id = $this->event->id;
                $event_notification->trooper_id = $trooper->id;

                if ($trooper->notification_frequency === NotificationFrequency::INSTANT)
                {
                    $event_notification->processed_at = now();
                    $event_notification->save();

                    Mail::to($trooper->email)->queue(new InstantEventNotification($event_notification));
                }
                else
                {
                    $event_notification->processed_at = null;
                    $event_notification->save();
                }
            }
        }

        $this->event->create_notifications_sent_at = now();
        $this->event->save();
    }
}
