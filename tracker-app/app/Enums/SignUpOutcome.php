<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What happened when a trooper signed up, or signed up a friend, from the event page
 * (SignUpEventTrooper).
 */
enum SignUpOutcome: string
{
    /**
     * They're on the shift (going, stand-by, pending or tentative).
     */
    case SIGNED_UP = 'signed_up';

    /**
     * The shift has stations and none (or one not on the shift) was picked.
     */
    case STATION_REQUIRED = 'station_required';

    /**
     * The trooper already has as many shifts on this event as it allows.
     */
    case SHIFT_LIMIT_REACHED = 'shift_limit_reached';

    /**
     * Not allowed for any other reason: the shift is closed, they're already on it, and so on.
     */
    case REFUSED = 'refused';
}
