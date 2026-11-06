<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\EventPosted;
use App\Messages\Events\Commands\SendEventCreatedNotification;
use App\Messages\Events\Queries\GetTroopersForEventCreated;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Creates event notifications for active troopers when a new event is posted.
 *
 * This job generates EventNotification records for all active troopers who have
 * valid email addresses and haven't already been notified about the event.
 * Troopers with instant notification preferences receive emails immediately,
 * while others have their notifications queued for batch processing.
 */
class SendEventCreatedNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Event  $event  The event to create notifications for.
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
     * Then fires EventPosted so plugins can announce the event, for example by
     * opening a forum thread.
     */
    public function handle(): void
    {
        if ($this->event->create_notifications_sent_at === null)
        {
            $this->sendTrooperNotifications();
        }

        EventPosted::dispatch($this->event);
    }

    /**
     * Notifications queue after commit, so the marker and the fan-out are atomic:
     * a failure rolls back the marker with nobody notified, and a retry re-runs
     * cleanly instead of skipping every trooper.
     */
    private function sendTrooperNotifications(): void
    {
        DB::transaction(function (): void
        {
            $this->event->create_notifications_sent_at = now();
            $this->event->save();

            $troopers = GetTroopersForEventCreated::call(event: $this->event);
            foreach ($troopers as $trooper)
            {
                SendEventCreatedNotification::call(event: $this->event, trooper: $trooper);
            }
        });
    }
}
