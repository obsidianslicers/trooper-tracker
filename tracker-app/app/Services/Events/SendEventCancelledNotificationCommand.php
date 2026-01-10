<?php

namespace App\Services\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Support\Facades\Mail;

/**
 * Service for sending event cancellation notifications to troopers.
 *
 * This service handles the actual email dispatch for event cancellations,
 * iterating through a collection of troopers and queueing cancellation
 * emails to those with valid email addresses.
 *
 * @package App\Services\Events
 */
class SendEventCancelledNotificationCommand
{
    /**
     * Send cancellation email to the given trooper.
     *
     * Queues a cancellation notification email to the trooper if they have a valid
     * email address. Invalid or missing email addresses are skipped.
     *
     * @param Event $event The cancelled event to notify about.
     * @param Trooper $trooper The Trooper model to notify.
     * @return void
     */
    public function __invoke(Event $event, Trooper $trooper): void
    {
        if ($trooper->emailAppearsValid())
        {
            Mail::to($trooper->email)->queue(new CancelledEventNotification($event));
        }
    }
}
