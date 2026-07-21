<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;

/**
 * Handler that deletes a shift station.
 *
 * Refuses to delete a station that still has roster signups; returns
 * whether the station was removed.
 *
 * @implements CommandHandlerInterface<RemoveEventShiftStationCommand>
 */
readonly class RemoveEventShiftStationCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  RemoveEventShiftStationCommand  $message
     */
    public function __invoke(object $message): bool
    {
        if ($message->event_shift_station->event_troopers()->exists())
        {
            return false;
        }

        return (bool) $message->event_shift_station->delete();
    }
}
