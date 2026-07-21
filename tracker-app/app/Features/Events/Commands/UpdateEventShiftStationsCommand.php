<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventShift;

/**
 * Command to create and update a shift's stations from form input.
 *
 * @see UpdateEventShiftStationsCommandHandler
 */
readonly class UpdateEventShiftStationsCommand
{
    /**
     * @param  array<int|string, array{name?: string|null, troopers_allowed?: int|string|null, sequence?: int|string|null}>  $stations  Station input keyed by station id (negative ids create new stations).
     */
    public function __construct(
        public EventShift $event_shift,
        public array $stations,
    ) {}
}
