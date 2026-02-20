<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;

/**
 * Command to sign up a trooper for an event shift.
 *
 * Creates an EventTrooper record linking a trooper to a specific event shift.
 * Tracks who added the trooper (for admin sign-ups vs self sign-ups).
 *
 * @see SignUpEventTrooperCommandHandler
 */
readonly class SignUpEventTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param  EventShift  $event_shift  The event shift to sign up for.
     * @param  Trooper  $trooper  The trooper signing up for the event.
     * @param  Trooper  $added_by_trooper  The trooper who added this sign-up (may be same as $trooper).
     */
    public function __construct(
        public readonly EventShift $event_shift,
        public readonly Trooper $trooper,
        public readonly Trooper $added_by_trooper,
    ) {}
}
