<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Features\Events\Commands\UpdateEventShiftStationsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Models\Event;
use App\Models\EventShift;
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

            $this->bus->send(new UpdateEventShiftStationsCommand(
                $shift,
                $input['stations'] ?? [],
            ));
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
}
