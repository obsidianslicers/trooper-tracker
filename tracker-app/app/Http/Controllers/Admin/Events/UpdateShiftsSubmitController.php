<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Jobs\ReconcileEventRosterJob;
use App\Messages\Events\Commands\UpdateEventShiftStations;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use App\Models\EventShift;
use Carbon\Carbon;
use Inertia\Response as InertiaResponse;

/**
 * Processes shift update form submissions.
 *
 * Handles creating new event shifts and updating existing shift start/end times.
 * Parses date and time inputs to create Carbon datetime objects for storage.
 */
class UpdateShiftsSubmitController extends EventCommandController
{
    /**
     * Updates or creates event shifts from the validated form submission
     *
     * Processes the validated request to create new shifts or update existing ones.
     * Parses date and time strings into Carbon datetime objects for database storage.
     * The tab saves as changes are made, so it answers without a flash message.
     *
     * @param  UpdateShiftsRequest  $request  The validated shift update request
     * @param  Event  $event  The event whose shifts are being updated (route model binding)
     * @return InertiaResponse The refreshed event page data as results
     */
    public function __invoke(UpdateShiftsRequest $request, Event $event): InertiaResponse
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
            else
            {
                if ($event->status === EventStatus::OPEN)
                {
                    $shift->status = EventStatus::OPEN;
                }
            }

            $shift->save();

            UpdateEventShiftStations::call(
                event_shift: $shift,
                stations: $input['stations'] ?? [],
            );
        }

        if (collect($shifts)->contains(fn (array $input) => !empty($input['stations'])))
        {
            dispatch(new ReconcileEventRosterJob($event, $request->user()));
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

        return $this->respondWithResults(
            $request,
            AdminEventPageData::SHIFTS,
            AdminEventPageData::CHARITY,
            AdminEventPageData::ROSTER,
        );
    }
}
