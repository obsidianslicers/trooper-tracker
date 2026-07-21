<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventShiftStation;

readonly class RemoveEventShiftStationCommand
{
    public function __construct(public EventShiftStation $event_shift_station) {}
}
