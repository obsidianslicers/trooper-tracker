<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Models\Event;
use App\Models\EventShift;
use App\Services\FlashMessageService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Processes shift update form submissions.
 *
 * Handles creating new event shifts and updating existing shift start/end times.
 * Parses date and time inputs to create Carbon datetime objects for storage.
 */
class UpdateShiftsSubmitController extends Controller
{
    /**
     * Creates a new UpdateShiftsSubmitController instance.
     *
     * @param FlashMessageService $flash Service for displaying flash messages to users.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Updates or creates event shifts from the validated form submission.
     *
     * Processes the validated request to create new shifts or update existing ones.
     * Parses date and time strings into Carbon datetime objects for database storage.
     * Redirects back to the shifts management page with a success message.
     *
     * @param UpdateShiftsRequest $request The validated shift update request.
     * @param Event $event The event whose shifts are being updated (route model binding).
     * @return RedirectResponse Redirect to the event's shift management page.
     */
    public function __invoke(UpdateShiftsRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $shifts = $request->validated('shifts');

        foreach ($shifts as $id => $input)
        {
            $shift = new EventShift();

            $shift->event_id = $event->id;

            if ($id > 0)
            {
                $shift = $event->event_shifts->filter(fn($s) => $s->id === $id)->first();
            }

            $shift->shift_starts_at = Carbon::parse($input['date'] . ' ' . $input['starts_at']);
            $shift->shift_ends_at = Carbon::parse($input['date'] . ' ' . $input['ends_at']);

            $shift->save();
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.shifts', compact('event'));
    }
}
