<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventShift;

readonly class ReorderEventShiftStationsCommand
{
    public function __construct(
        public EventShift $event_shift,
        public array $ordered_ids,
    ) {}
}
