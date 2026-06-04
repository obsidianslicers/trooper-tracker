<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Features\Events\Commands\UpdateEventGuestCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\GuestUpdateHtmxRequest;
use App\Models\EventGuest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles HTMX-driven updates to event guest details.
 *
 * Processes validated guest update payloads and dispatches an
 * {@see UpdateEventGuestCommand} through the magic bus.
 */
class GuestUpdateHtmxController extends MagicBusController
{
    /**
     * Processes an HTMX guest update request.
     *
     * Validates the incoming payload and updates either the guest status or
     * guest name based on the provided input key.
     */
    public function __invoke(GuestUpdateHtmxRequest $request, EventGuest $event_guest): Response
    {
        $request->validateInputs();

        $authTrooper = Auth::user();

        if ($request->has('status'))
        {
            $event_shift = $event_guest->event_shift;
            $event = $event_shift->event;
            $requestedStatus = EventGuestStatus::from($request->validated('status'));
            $canModerateEvent = $authTrooper->can('update', $event);
            $isManualSelectionEvent = $event->status === EventStatus::MANUAL_SELECTION;
            $isManualApproval = $isManualSelectionEvent
                && $canModerateEvent
                && $event_guest->status === EventGuestStatus::STAND_BY
                && $requestedStatus === EventGuestStatus::GOING;
            $isManualRejection = $isManualSelectionEvent
                && $canModerateEvent
                && $event_guest->status === EventGuestStatus::GOING
                && $requestedStatus === EventGuestStatus::STAND_BY;

            if ($isManualSelectionEvent && $requestedStatus === EventGuestStatus::GOING && !$canModerateEvent)
            {
                return response('Forbidden', 403);
            }

            if (!$event_guest->canUpdateStatus($authTrooper) && !$isManualApproval && !$isManualRejection)
            {
                return response('Forbidden', 403);
            }

            $valid_data = ['status' => $requestedStatus];

            $event_guest_cmd = new UpdateEventGuestCommand($event_guest, $valid_data);

            $this->bus->send($event_guest_cmd);

            $trooper = $authTrooper;

            $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $trooper);

            $event_shift = $this->bus->send($event_shift_query);

            $event = $event_shift->event;

            $can_moderate = $trooper->isModeratorForOrganization($event->organization);

            $count_of_shifts = $event->event_shifts()->count();

            $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');

            $data['open'] = true;

            return response()->view('pages.events.inc.shift-container', $data);
        }
        elseif ($request->has('name'))
        {
            //  costume organization ids handled via observer, so we
            //  only need to update the name on the event guest record here
            $valid_data = [
                EventGuest::NAME => $request->validated('name'),
            ];

            $event_guest_cmd = new UpdateEventGuestCommand($event_guest, $valid_data);

            $this->bus->send($event_guest_cmd);
        }

        return response('ok', 200);
    }
}
