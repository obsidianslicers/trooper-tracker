<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What happened when command staff added a trooper to a shift's roster.
 */
enum AddEventTrooperOutcome: string
{
    /**
     * The trooper is on the roster.
     */
    case ADDED = 'added';

    /**
     * The trooper was already on this shift, so nothing changed.
     */
    case ALREADY_SIGNED_UP = 'already_signed_up';

    /**
     * The shift has stations and none (or one not on the shift) was picked.
     */
    case STATION_REQUIRED = 'station_required';
}
