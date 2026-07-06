<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Processes shift update form submissions.
 *
 * Handles creating new event shifts and updating existing shift start/end times.
 * Parses date and time inputs to create Carbon datetime objects for storage.
 */
class UpdateShiftsSubmitController extends MagicBusController
{
    /**
     * Updates or creates event shifts from the validated form submission
     *
     * Processes the validated request to create new shifts or update existing ones.
     * Parses date and time strings into Carbon datetime objects for database storage.
     * Redirects back to the shifts management page with a success message.
     *
     * @param  UpdateShiftsRequest  $request  The validated shift update request
     * @param  Event  $event  The event whose shifts are being updated (route model binding)
     * @return RedirectResponse Redirect to the event's shift management page
     */
    public function __invoke(UpdateShiftsRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->load('event_shifts.event_shift_stations');

        $shifts = $request->validated('shifts', []);

        foreach ($shifts as $id => $input)
        {
            $shift = new EventShift;

            $shift->event_id = $event->id;

            if ($id > 0)
            {
                $shift = $event->event_shifts->filter(fn ($s) => $s->id === $id)->first();
            }

            $shift->shift_starts_at = Carbon::parse($input['date'].' '.$input['starts_at']);
            $shift->shift_ends_at = Carbon::parse($input['date'].' '.$input['ends_at']);

            if (isset($input['status']))
            {
                $shift->status = EventStatus::from($input['status']);
            }

            $shift->save();

            foreach ($input['stations'] ?? [] as $station_id => $station_input)
            {
                $name = trim((string) ($station_input['name'] ?? ''));
                $troopers_allowed = $station_input['troopers_allowed'] ?? null;

                if ($name === '' && empty($troopers_allowed))
                {
                    continue;
                }

                if ($id <= 0)
                {
                    continue;
                }

                $station = new EventShiftStation;
                $station->event_shift_id = $shift->id;

                if ((int) $station_id > 0)
                {
                    $station = $shift->event_shift_stations->firstWhere('id', (int) $station_id);

                    if ($station === null)
                    {
                        continue;
                    }
                }

                $station->name = $name;
                $station->troopers_allowed = (int) $troopers_allowed;

                if (array_key_exists('sequence', $station_input))
                {
                    $station->sequence = (int) $station_input['sequence'];
                }
                elseif (!$station->exists)
                {
                    $station->sequence = ((int) $shift->event_shift_stations()->max(EventShiftStation::SEQUENCE)) + 10;
                }

                $station->save();

                $this->promoteStationStandbysWhileRoom($station);
            }
        }

        $starts_at = $event->event_shifts()->min(EventShift::SHIFT_STARTS_AT);
        $ends_at = $event->event_shifts()->max(EventShift::SHIFT_ENDS_AT);

        if ($starts_at !== null && $ends_at !== null)
        {
            $event->event_start = Carbon::parse($starts_at);
            $event->event_end = Carbon::parse($ends_at);

            if ($event->isDirty([Event::EVENT_START, Event::EVENT_END]))
            {
                $event->save();
            }
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.shifts', compact('event'));
    }

    private function promoteStationStandbysWhileRoom(EventShiftStation $station): void
    {
        while ($station->fresh()->hasRoom())
        {
            $next_in_line = $station->event_troopers()
                ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
                ->orderBy(EventTrooper::SIGNED_UP_AT)
                ->first();

            if ($next_in_line === null)
            {
                return;
            }

            $next_in_line->status = EventTrooperStatus::GOING;
            $next_in_line->save();

            $next_in_line->trooper->notify(new TrooperPromotedToGoingNotification($next_in_line));
        }
    }
}
