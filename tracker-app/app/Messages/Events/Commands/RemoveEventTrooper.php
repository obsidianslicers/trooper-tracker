<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventTrooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Takes a sign-up off the roster. When it held a spot, the next in line moves up into it.
 *
 * @method static void call(EventTrooper $event_trooper)
 */
final class RemoveEventTrooper extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function handle(): void
    {
        if ($this->event_trooper->intendsToGo())
        {
            PromoteNextInLineEventTrooper::call(
                event_trooper: $this->event_trooper,
                event_shift_station_id: $this->event_trooper->event_shift_station_id,
            );
        }

        $this->event_trooper->delete();
    }
}
