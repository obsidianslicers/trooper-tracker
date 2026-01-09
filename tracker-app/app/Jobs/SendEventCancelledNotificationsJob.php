<?php

namespace App\Jobs;

use App\Enums\EventTrooperStatus;
use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\Events\GetTroopersForCancelledEventQuery;
use App\Services\Events\SendCancelledEventNotificationsCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends cancellation notifications to troopers who signed up for a cancelled event.
 *
 * This job notifies all troopers with a "going" status for event shifts belonging
 * to a cancelled event. Only troopers with valid email addresses receive notifications.
 * The job runs once per event, tracked by the create_notifications_sent_at timestamp.
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
     * shift in the cancelled event. Updates the event's create_notifications_sent_at
     * timestamp to prevent duplicate notifications.
     *
     * @param GetTroopersForCancelledEventQuery $get_troopers Service to query troopers who signed up for the event.
     * @param SendCancelledEventNotificationsCommand $send_emails Service to send cancellation notifications.
     * @return void
     */
    public function handle(
        GetTroopersForCancelledEventQuery $get_troopers,
        SendCancelledEventNotificationsCommand $send_emails): void
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
