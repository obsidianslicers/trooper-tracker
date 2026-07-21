<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventShift;

readonly class UpdateEventShiftStationsCommand
{
    public function __construct(
        public EventShift $event_shift,
        public array $stations,
    ) {}
}
