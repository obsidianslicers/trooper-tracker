<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What happened when a trooper toggled watching an event (ToggleEventWatch).
 */
enum EventWatchOutcome: string
{
    /**
     * They now watch the event.
     */
    case WATCHING = 'watching';

    /**
     * They no longer watch the event.
     */
    case NOT_WATCHING = 'not_watching';

    /**
     * A listener on EventWatchChanging (a plugin) kept things as they were.
     */
    case UNCHANGED = 'unchanged';
}
