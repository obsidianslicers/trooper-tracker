<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;

readonly class UpdateEventShiftStationsCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        $message->event_shift->loadMissing('event_shift_stations');

        foreach ($message->stations as $station_id => $station_input)
        {
            $name = trim((string) ($station_input['name'] ?? ''));
            $troopers_allowed = $station_input['troopers_allowed'] ?? null;

            if ($name === '' && empty($troopers_allowed))
            {
                continue;
            }

            $station = $this->resolveStation($message->event_shift, (int) $station_id);

            if ($station === null)
            {
                continue;
            }

            $station->name = $name;
            $station->troopers_allowed = (int) $troopers_allowed;
            $station->sequence = isset($station_input['sequence'])
                ? (int) $station_input['sequence']
                : $station->sequence;
            $station->save();

            $this->reconcileRoster($station);
        }

        return null;
    }

    private function resolveStation(EventShift $event_shift, int $station_id): ?EventShiftStation
    {
        if ($station_id > 0)
        {
            return $event_shift->event_shift_stations->firstWhere(EventShiftStation::ID, $station_id);
        }

        $station = new EventShiftStation;
        $station->event_shift_id = $event_shift->id;
        $station->sequence = ((int) $event_shift->event_shift_stations()
            ->max(EventShiftStation::SEQUENCE)) + 10;

        return $station;
    }

    private function reconcileRoster(EventShiftStation $station): void
    {
        $going = $station->going_event_troopers()
            ->orderByDesc(EventTrooper::SIGNED_UP_AT)
            ->get();

        $going->take(max(0, $going->count() - $station->troopers_allowed))
            ->each(function (EventTrooper $event_trooper): void {
                $event_trooper->update([EventTrooper::STATUS => EventTrooperStatus::STAND_BY]);
            });

        $open_slots = $station->troopers_allowed - $station->going_event_troopers()->count();
        $station->event_troopers()
            ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
            ->orderBy(EventTrooper::SIGNED_UP_AT)
            ->limit(max(0, $open_slots))
            ->get()
            ->each(function (EventTrooper $event_trooper): void {
                $event_trooper->update([EventTrooper::STATUS => EventTrooperStatus::GOING]);
                $event_trooper->trooper->notify(new TrooperPromotedToGoingNotification($event_trooper));
            });
    }
}
