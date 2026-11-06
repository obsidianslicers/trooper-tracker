<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Services\EventRosterCapacityService;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Command to move an event trooper to a different station on their shift.
 *
 * @method static void call(EventTrooper $event_trooper, ?int $event_shift_station_id)
 */
final class ChangeEventTrooperStation extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly ?int $event_shift_station_id,
    ) {}

    public function handle(EventRosterCapacityService $capacity): void
    {
        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;

        $old_station_id = $event_trooper->event_shift_station_id;
        $old_station_was_full = $old_station_id !== null
            && $event_shift->stationMaxed($old_station_id);

        $new_station_id = $this->event_shift_station_id;

        $valid_data = [EventTrooper::EVENT_SHIFT_STATION_ID => $new_station_id];

        $new_station_has_room = $capacity->canGoAtStation(
            $event_shift,
            $new_station_id,
            $event_trooper->id,
        );

        if ($event_trooper->intendsToGo() && !$new_station_has_room)
        {
            $valid_data[EventTrooper::STATUS] = EventTrooperStatus::STAND_BY;
        }

        UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

        if ($old_station_id !== null && $old_station_id !== $new_station_id && $old_station_was_full)
        {
            PromoteNextInLineEventTrooper::call(
                event_trooper: $event_trooper,
                event_shift_station_id: $old_station_id,
            );
        }
    }
}
