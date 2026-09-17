<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Command to send an event-updated notification to a trooper.
 *
 * @see SendEventUpdatedNotificationCommandHandler
 */
readonly class SendEventUpdatedNotificationCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event that was updated.
     * @param  Trooper  $trooper  The trooper to notify.
     * @param  array<int, string>  $changed_fields  The event attributes that changed.
     */
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
        public readonly array $changed_fields,
    ) {}
}
