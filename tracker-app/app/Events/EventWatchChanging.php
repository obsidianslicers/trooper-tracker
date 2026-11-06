<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use App\Models\Trooper;

/**
 * A trooper is about to start or stop watching an event.
 *
 * Fired with Event::until() before the watch is saved. A listener that returns
 * false stops the change, for example when a linked forum subscription could not
 * be updated; it should flash the reason.
 */
final class EventWatchChanging
{
    public function __construct(
        public readonly Event $event,
        public readonly Trooper $trooper,
        public readonly bool $watching,
    ) {}
}
