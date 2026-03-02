<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;

/**
 * Command to share an event roster with a coordinator.
 *
 * Creates an EventShare record with a shareable token that allows
 * the recipient to view the event roster via email. The share expires
 * one day after the event ends.
 *
 * @see ShareEventRosterCommandHandler
 */
readonly class ShareEventRosterCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event whose roster is being shared.
     * @param  string  $recipient_email  The email address to share the roster with.
     * @param  Trooper  $shared_by_trooper  The trooper who is sharing the roster.
     */
    public function __construct(
        public readonly Event $event,
        public readonly string $recipient_email,
        public readonly Trooper $shared_by_trooper,
    ) {
    }
}
