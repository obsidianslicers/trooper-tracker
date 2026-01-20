<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Command to create event notification for a trooper when a new event is published.
 *
 * Creates an EventNotification record and optionally sends an instant email
 * based on the trooper's notification frequency preference (INSTANT vs DAILY).
 *
 * @see SendEventCreatedNotificationCommandHandler
 */
readonly class SendEventCreatedNotificationCommand
{
    /**
     * Create a new command instance.
     *
     * @param Event $event The event to create notification for.
     * @param Trooper $trooper The trooper to notify.
     */
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
    ) {
    }
}
