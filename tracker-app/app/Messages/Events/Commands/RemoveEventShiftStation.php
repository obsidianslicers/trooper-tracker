<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventShiftStation;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Command to delete a shift station that has no roster signups.
 *
 * @method static bool call(EventShiftStation $event_shift_station)
 */
final class RemoveEventShiftStation extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly EventShiftStation $event_shift_station) {}

    public function handle(): bool
    {
        if ($this->event_shift_station->event_troopers()->exists())
        {
            return false;
        }

        return (bool) $this->event_shift_station->delete();
    }
}
