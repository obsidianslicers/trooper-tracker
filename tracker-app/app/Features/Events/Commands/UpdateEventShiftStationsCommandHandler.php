<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use InvalidArgumentException;

/**
 * Handler that creates and updates a shift's stations from form input.
 *
 * Persists station names, limits, and ordering only. Roster consequences of
 * a limit change (promotions / demotions) are handled by dispatching
 * ReconcileEventRosterCommand afterwards, so capacity rules live in exactly
 * one place.
 *
 * @implements CommandHandlerInterface<UpdateEventShiftStationsCommand>
 */
readonly class UpdateEventShiftStationsCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  UpdateEventShiftStationsCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $message->event_shift->loadMissing('event_shift_stations');

        foreach ($message->stations as $station_id => $station_input)
        {
            $this->upsertStation($message->event_shift, (int) $station_id, $station_input);
        }

        return null;
    }

    /**
     * @param  array{name?: string|null, troopers_allowed?: int|string|null, sequence?: int|string|null}  $station_input
     */
    private function upsertStation(EventShift $event_shift, int $station_id, array $station_input): void
    {
        $name = trim((string) ($station_input['name'] ?? ''));
        $troopers_allowed = $station_input['troopers_allowed'] ?? null;

        if ($name === '' && empty($troopers_allowed))
        {
            return;
        }

        //  stations always require a positive numerical limit; request
        //  validation guards this, so reaching here without one is a bug
        if (!is_numeric($troopers_allowed) || (int) $troopers_allowed < 1)
        {
            throw new InvalidArgumentException(
                'Station troopers_allowed must be a positive integer.'
            );
        }

        $station = $this->resolveStation($event_shift, $station_id);

        if ($station === null)
        {
            return;
        }

        $station->name = $name;
        $station->troopers_allowed = (int) $troopers_allowed;
        $station->sequence = isset($station_input['sequence'])
            ? (int) $station_input['sequence']
            : $station->sequence;
        $station->save();
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
}
