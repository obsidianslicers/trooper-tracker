<?php

namespace App\Jobs;

use App\Enums\EventTrooperStatus;
use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
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
     * @return void
     */
    public function handle(): void
    {
        if ($this->event->create_notifications_sent_at !== null)
        {
            return;
        }

        $event_id = $this->event->id;

        $filter = function ($q) use ($event_id)
        {
            $q->where(EventTrooper::STATUS, EventTrooperStatus::GOING)
                ->whereHas('event_shift', function ($q) use ($event_id)
                {
                    $q->where(EventShift::EVENT_ID, $event_id);
                });
        };

        $troopers = Trooper::active()->whereHas('event_troopers', $filter)->get();

        foreach ($troopers as $trooper)
        {
            if ($trooper->emailAppearsValid())
            {
                Mail::to($trooper->email)->queue(new CancelledEventNotification($this->event));
            }
        }

        $this->event->create_notifications_sent_at = now();
        $this->event->save();
    }
}
