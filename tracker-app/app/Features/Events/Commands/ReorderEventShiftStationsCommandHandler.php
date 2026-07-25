<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventShiftStation;

/**
 * Handler that re-sequences a shift's stations from an ordered id list.
 *
 * Ids that don't belong to the shift are ignored.
 *
 * @implements CommandHandlerInterface<ReorderEventShiftStationsCommand>
 */
readonly class ReorderEventShiftStationsCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  ReorderEventShiftStationsCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        foreach ($message->ordered_ids as $position => $id)
        {
            EventShiftStation::where(EventShiftStation::EVENT_SHIFT_ID, $message->event_shift->id)
                ->where(EventShiftStation::ID, (int) $id)
                ->update([EventShiftStation::SEQUENCE => ($position + 1) * 10]);
        }

        return null;
    }
}
