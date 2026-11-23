<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Jobs\ReconcileEventRosterJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Saves the event's shifts and their stations, then moves the event's start and end to fit them.
 *
 * A shift id of 0 or less adds a new shift. Changing stations reconciles the roster.
 *
 * @method static void call(Event $event, array $shifts, Trooper $updated_by_trooper)
 */
final class UpdateEventShifts extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  array<int, array<string, mixed>>  $shifts  Shift input keyed by shift id
     */
    public function __construct(
        private readonly Event $event,
        private readonly array $shifts,
        private readonly Trooper $updated_by_trooper,
    ) {}

    public function handle(): void
    {
        $this->event->load('event_shifts.event_shift_stations');

        foreach ($this->shifts as $id => $input)
        {
            $shift = $this->saveShift((int) $id, $input);

            UpdateEventShiftStations::call(
                event_shift: $shift,
                stations: $input['stations'] ?? [],
            );
        }

        if (collect($this->shifts)->contains(fn (array $input) => !empty($input['stations'])))
        {
            dispatch(new ReconcileEventRosterJob($this->event, $this->updated_by_trooper))->afterCommit();
        }

        $this->fitEventToShifts();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function saveShift(int $id, array $input): EventShift
    {
        $shift = new EventShift;
        $shift->event_id = $this->event->id;

        if ($id > 0)
        {
            $shift = $this->event->event_shifts->filter(fn ($s) => $s->id === $id)->first();
        }

        $shift->shift_starts_at = Carbon::parse($input['date'].' '.$input['starts_at']);
        $shift->shift_ends_at = Carbon::parse($input['date'].' '.$input['ends_at']);

        if (isset($input['status']))
        {
            $shift->status = EventStatus::from($input['status']);
        }
        elseif ($this->event->status === EventStatus::OPEN)
        {
            $shift->status = EventStatus::OPEN;
        }

        $shift->save();

        return $shift;
    }

    private function fitEventToShifts(): void
    {
        $starts_at = $this->event->event_shifts()->min(EventShift::SHIFT_STARTS_AT);
        $ends_at = $this->event->event_shifts()->max(EventShift::SHIFT_ENDS_AT);

        if ($starts_at === null || $ends_at === null)
        {
            return;
        }

        $this->event->event_start = Carbon::parse($starts_at);
        $this->event->event_end = Carbon::parse($ends_at);

        if ($this->event->isDirty([Event::EVENT_START, Event::EVENT_END]))
        {
            $this->event->save();
        }
    }
}
