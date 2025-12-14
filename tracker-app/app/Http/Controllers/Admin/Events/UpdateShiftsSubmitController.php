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
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class UpdateShiftsSubmitController extends Controller
{
    /**
     * UpdateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the request to display the event update page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing event.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
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
