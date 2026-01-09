<?php

namespace App\Services\Events;

use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
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
class SendCancelledEventNotificationsCommand
{
    /**
     * Send cancellation emails to the given troopers.
     *
     * Iterates through the provided trooper collection and queues a
     * cancellation notification email to each trooper with a valid
     * email address. Invalid or missing email addresses are skipped.
     *
     * @param Event $event The cancelled event to notify about.
     * @param iterable $troopers Collection of Trooper models to notify.
     * @return void
     */
    public function __invoke(Event $event, iterable $troopers): void
    {
        foreach ($troopers as $trooper)
        {
            if ($trooper->emailAppearsValid())
            {
                Mail::to($trooper->email)
                    ->queue(new CancelledEventNotification($event));
            }
        }
    }
}
