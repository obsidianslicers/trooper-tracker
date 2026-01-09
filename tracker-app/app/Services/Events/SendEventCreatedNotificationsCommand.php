<?php

namespace App\Services\Events;

use App\Enums\NotificationFrequency;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Service for creating event notifications and sending instant emails to troopers.
 *
 * This service handles the creation of EventNotification records for eligible
 * troopers and immediately sends emails to those with instant notification
 * preferences. Troopers with daily notification preferences have records
 * created but emails are deferred for batch processing.
 *
 * @package App\Services\Events
 */
class SendEventCreatedNotificationsCommand
{
    /**
     * Create event notifications and send instant emails to eligible troopers.
     *
     * Iterates through the provided trooper collection and creates EventNotification
     * records for each trooper with a valid email address. Troopers with instant
     * notification preferences receive immediate emails, while others have their
     * notifications queued for daily digest processing.
     *
     * @param Event $event The event to create notifications for.
     * @param iterable $troopers Collection of Trooper models to notify.
     * @return void
     */
    public function __invoke(Event $event, iterable $troopers): void
    {
        foreach ($troopers as $trooper)
        {
            if (!$trooper->emailAppearsValid())
            {
                continue;
            }

            $notification = new EventNotification();
            $notification->event_id = $event->id;
            $notification->trooper_id = $trooper->id;

            if ($trooper->notification_frequency === NotificationFrequency::INSTANT)
            {
                $notification->processed_at = now();
                $notification->save();

                Mail::to($trooper->email)
                    ->queue(new InstantEventNotification($notification));
            }
            else
            {
                $notification->processed_at = null;
                $notification->save();
            }
        }
    }
}
