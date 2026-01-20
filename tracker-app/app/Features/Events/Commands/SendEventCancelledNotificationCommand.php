<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Command to send event cancellation notification to a trooper.
 *
 * Triggers an email notification to a trooper who had signed up for an event
 * that has been cancelled. Unlike regular event notifications, cancellation
 * notifications are sent regardless of the trooper's notification preferences.
 *
 * @see SendEventCancelledNotificationCommandHandler
 */
readonly class SendEventCancelledNotificationCommand
{
    /**
     * Create a new command instance.
     *
     * @param Event $event The cancelled event.
     * @param Trooper $trooper The trooper who signed up for the event.
     */
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
    ) {
    }
}
