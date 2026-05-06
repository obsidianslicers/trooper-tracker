<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Mail\Events\CancelledEventNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Support\Facades\Mail;

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
    public function __construct(private PushNotificationService $push) {}

    /**
     * Send cancellation notification email to a trooper.
     *
     * Validates the trooper has a valid email address, then queues a
     * CancelledEventNotification email. Does not create EventNotification
     * records as cancellations are tracked differently.
     *
     * @param  SendEventCancelledNotificationCommand  $message  The command containing event and trooper
     * @return null Always returns null
     */
    public function __invoke(object $message): mixed
    {
        $this->push->sendToTrooper(
            $message->trooper,
            'Event Cancelled: ' . $message->event->name,
            'This event has been cancelled.',
        );

        if ($message->trooper->emailAppearsValid())
        {
            Mail::to($message->trooper->email)->queue(new CancelledEventNotification($message->event));
        }

        return null;
    }
}
