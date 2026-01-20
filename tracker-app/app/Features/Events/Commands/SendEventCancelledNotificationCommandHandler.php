<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\Events\CancelledEventNotification;
use App\Mail\Events\InstantEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Support\Facades\Mail;
use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler for sending event cancellation notifications to individual troopers.
 *
 * Sends a CancelledEventNotification email to troopers who had signed up
 * for a cancelled event. Notifications are sent regardless of the trooper's
 * notification frequency preference since they committed to the event.
 *
 * @implements CommandHandlerInterface<SendEventCancelledNotificationCommand>
 */
readonly class SendEventCancelledNotificationCommandHandler implements CommandHandlerInterface
{
    /**
     * Send cancellation notification email to a trooper.
     *
     * Validates the trooper has a valid email address, then queues a
     * CancelledEventNotification email. Does not create EventNotification
     * records as cancellations are tracked differently.
     *
     * @param SendEventCancelledNotificationCommand $message The command containing event and trooper
     * @return null Always returns null
     */
    public function __invoke(object $message): mixed
    {
        if ($message->trooper->emailAppearsValid())
        {
            Mail::to($message->trooper->email)->queue(new CancelledEventNotification($message->event));
        }

        return null;
    }
}
