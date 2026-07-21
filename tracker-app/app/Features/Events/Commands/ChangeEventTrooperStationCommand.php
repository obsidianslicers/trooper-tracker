<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

/**
 * Command to move an event trooper to a different station on their shift.
 *
 * @see ChangeEventTrooperStationCommandHandler
 */
readonly class ChangeEventTrooperStationCommand
{
    public function __construct(
        public EventTrooper $event_trooper,
        public ?int $event_shift_station_id,
    ) {}
}
