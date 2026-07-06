<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventShiftStation;

readonly class ReorderEventShiftStationsCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

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
