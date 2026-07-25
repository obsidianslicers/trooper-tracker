<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventShiftStation;

/**
 * Command to delete a shift station that has no roster signups.
 *
 * @see RemoveEventShiftStationCommandHandler
 */
readonly class RemoveEventShiftStationCommand
{
    public function __construct(public EventShiftStation $event_shift_station) {}
}
