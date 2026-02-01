<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventCancelledQuery;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends cancellation notifications to troopers who signed up for a cancelled event.
 *
 * This job notifies all troopers with a "going" status for event shifts belonging
 * to a cancelled event. Only troopers with valid email addresses receive notifications.
 * The job runs once per event, tracked by the cancel_notifications_sent_at timestamp.
 *
 * @package App\Jobs
 */
class SendEventCancelledNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param Event $event The cancelled event to send notifications for.
     */
    public function __construct(private readonly Event $event)
    {
        //
    }

    /**
     * Execute the job.
     *
     * Sends cancellation emails to all troopers who were marked as "going" for any
     * shift in the cancelled event. Updates the event's cancel_notifications_sent_at
     * timestamp to prevent duplicate notifications.
     *
     * @return void
     */
    public function handle(MagicBus $bus): void
    {
        if ($this->event->cancel_notifications_sent_at !== null)
        {
            return;
        }

        $troopers_query = new GetTroopersForEventCancelledQuery($this->event);

        $troopers = $bus->send($troopers_query);

        foreach ($troopers as $trooper)
        {
            $send_notification_command = new SendEventCancelledNotificationCommand($this->event, $trooper);

            $bus->send($send_notification_command);
        }

        $this->event->cancel_notifications_sent_at = now();
        $this->event->save();
    }
}
