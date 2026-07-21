<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventShift;

/**
 * Command to persist a new display order for a shift's stations.
 *
 * @see ReorderEventShiftStationsCommandHandler
 */
readonly class ReorderEventShiftStationsCommand
{
    /**
     * @param  array<int, int|string>  $ordered_ids  Station ids in the desired display order.
     */
    public function __construct(
        public EventShift $event_shift,
        public array $ordered_ids,
    ) {}
}
