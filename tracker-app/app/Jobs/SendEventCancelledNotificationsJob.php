<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
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
 */
class SendEventCancelledNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Event  $event  The cancelled event to send notifications for.
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
     */
    public function handle(MagicBus $bus): void
    {
        if ($this->event->cancel_notifications_sent_at !== null)
        {
            return;
        }

        // Resolve recipients before mutating statuses: the query selects troopers
        // whose EventTrooper status is still GOING.
        $troopers_query = new GetTroopersForEventCancelledQuery($this->event);

        $troopers = $bus->send($troopers_query);

        foreach ($this->event->event_shifts as $shift)
        {
            $shift->status = EventStatus::CANCELLED;
            $shift->save();

            foreach ($shift->event_troopers as $event_trooper)
            {
                if ($event_trooper->status === EventTrooperStatus::CANCELLED)
                {
                    continue;
                }

                $event_trooper->status = EventTrooperStatus::CANCELLED;
                $event_trooper->save();
            }
        }

        // Mark sent before dispatching so a retry short-circuits at the guard
        // above instead of re-sending cancellation notices to the whole roster.
        $this->event->cancel_notifications_sent_at = now();
        $this->event->save();

        foreach ($troopers as $trooper)
        {
            $send_notification_command = new SendEventCancelledNotificationCommand($this->event, $trooper);

            $bus->send($send_notification_command);
        }
    }
}
