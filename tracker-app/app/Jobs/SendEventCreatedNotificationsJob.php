<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCreatedQuery;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(MagicBus $bus): void
    {
        if ($this->event->create_notifications_sent_at !== null)
        {
            return;
        }

        $troopers_query = new GetTroopersForEventCreatedQuery($this->event);

        $troopers = $bus->send($troopers_query);

        foreach ($troopers as $trooper)
        {
            $send_notification_command = new SendEventCreatedNotificationCommand($this->event, $trooper);

            $bus->send($send_notification_command);
        }

        $this->event->create_notifications_sent_at = now();
        $this->event->save();
    }
}
