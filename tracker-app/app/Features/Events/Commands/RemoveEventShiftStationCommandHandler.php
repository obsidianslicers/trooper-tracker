<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;

readonly class RemoveEventShiftStationCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): bool
    {
        if ($message->event_shift_station->event_troopers()->exists())
        {
            return false;
        }

        return (bool) $message->event_shift_station->delete();
    }
}
