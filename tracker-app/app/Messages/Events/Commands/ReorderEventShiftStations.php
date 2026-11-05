<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventShift;
use App\Models\EventShiftStation;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Command to persist a new display order for a shift's stations.
 *
 * @method static void call(EventShift $event_shift, array $ordered_ids)
 */
final class ReorderEventShiftStations extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  array<int, int|string>  $ordered_ids  Station ids in the desired display order.
     */
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly array $ordered_ids,
    ) {}

    public function handle(): void
    {
        foreach ($this->ordered_ids as $position => $id)
        {
            EventShiftStation::where(EventShiftStation::EVENT_SHIFT_ID, $this->event_shift->id)
                ->where(EventShiftStation::ID, (int) $id)
                ->update([EventShiftStation::SEQUENCE => ($position + 1) * 10]);
        }
    }
}
