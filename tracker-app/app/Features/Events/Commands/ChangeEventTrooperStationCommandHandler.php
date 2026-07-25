<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Services\EventRosterCapacityService;

/**
 * Handler that moves an event trooper to a different station.
 *
 * A GOING trooper moving into a full station is demoted to STAND_BY. When
 * the trooper vacates a station that was full, the next standby in that
 * station's queue is promoted.
 *
 * @implements CommandHandlerInterface<ChangeEventTrooperStationCommand>
 */
readonly class ChangeEventTrooperStationCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __construct(
        private MagicBus $bus,
        private EventRosterCapacityService $capacity,
    ) {}

    /**
     * @param  ChangeEventTrooperStationCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $event_trooper = $message->event_trooper;
        $event_shift = $event_trooper->event_shift;

        $old_station_id = $event_trooper->event_shift_station_id;
        $old_station_was_full = $old_station_id !== null
            && $event_shift->stationMaxed($old_station_id);

        $new_station_id = $message->event_shift_station_id;

        $valid_data = [EventTrooper::EVENT_SHIFT_STATION_ID => $new_station_id];

        $new_station_has_room = $this->capacity->canGoAtStation(
            $event_shift,
            $new_station_id,
            $event_trooper->id,
        );

        if ($event_trooper->intendsToGo() && !$new_station_has_room)
        {
            $valid_data[EventTrooper::STATUS] = EventTrooperStatus::STAND_BY;
        }

        $this->bus->send(new UpdateEventTrooperCommand($event_trooper, $valid_data));

        if ($old_station_id !== null && $old_station_id !== $new_station_id && $old_station_was_full)
        {
            $this->bus->send(new PromoteNextInLineEventTrooperCommand(
                $event_trooper,
                event_shift_station_id: $old_station_id,
            ));
        }

        return null;
    }
}
