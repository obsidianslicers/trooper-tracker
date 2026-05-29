<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\AttendanceUpdateHtmxRequest;
use App\Models\EventTrooper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles HTMX-driven updates to event trooper attendance details.
 */
class AttendanceUpdateHtmxController extends MagicBusController
{
    public function __invoke(AttendanceUpdateHtmxRequest $request, EventTrooper $event_trooper): Response
    {
        $request->validateInputs();
        $authTrooper = Auth::user();

        $event_shift = $event_trooper->event_shift;
        $event = $event_shift->event;

        $valid_data = ['status' => $request->validated('status')];

        $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
        $this->bus->send($event_trooper_cmd);

        $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $authTrooper);
        $event_shift = $this->bus->send($event_shift_query);
        $event = $event_shift->event;

        $can_moderate = $authTrooper->isModeratorForOrganization($event->organization);
        $count_of_shifts = $event->event_shifts()->count();

        $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');
        $data['open'] = true;

        return response()->view('pages.events.inc.shift-container', $data);
    }
}
