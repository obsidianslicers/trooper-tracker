<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Trooper;

/**
 * Command to send daily event notification digest to a trooper.
 *
 * Sends a consolidated daily digest email containing all pending event
 * notifications for a trooper who has opted for daily notifications.
 *
 * @see SendEventDailyNotificationCommandHandler
 */
readonly class SendEventDailyNotificationCommand
{
    /**
     * Create a new command instance.
     *
     * @param Trooper $trooper The trooper to send daily notifications to
     */
    public function __construct(
        public readonly Trooper $trooper
    ) {
    }
}
