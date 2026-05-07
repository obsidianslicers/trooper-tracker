<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\SignupUpdateHtmxRequest;
use App\Models\EventTrooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles HTMX-driven updates to event trooper sign-up details.
 */
class SignUpUpdateHtmxController extends MagicBusController
{
    /**
     * @param  SignupUpdateHtmxRequest  $request
     * @param  EventTrooper  $event_trooper
     */
    public function __invoke(SignupUpdateHtmxRequest $request, EventTrooper $event_trooper): Response
    {
        $request->validateInputs();

        if ($request->has('status'))
        {
            $event_shift = $event_trooper->event_shift;
            $event = $event_shift->event;
            $authTrooper = Auth::user();
            $requestedStatus = EventTrooperStatus::from($request->validated('status'));
            $canModerateEvent = $authTrooper->can('update', $event);
            $isManualSelectionEvent = $event->status === EventStatus::MANUAL_SELECTION;
            $isManualApproval = $isManualSelectionEvent
                && $canModerateEvent
                && $event_trooper->status === EventTrooperStatus::STAND_BY
                && $requestedStatus === EventTrooperStatus::GOING;
            $isManualRejection = $isManualSelectionEvent
                && $canModerateEvent
                && $event_trooper->status === EventTrooperStatus::GOING
                && $requestedStatus === EventTrooperStatus::STAND_BY;

            if ($isManualSelectionEvent && $requestedStatus === EventTrooperStatus::GOING && !$canModerateEvent)
            {
                return response('Forbidden', 403);
            }

            if (!$event_trooper->canUpdateStatus($event_shift, $authTrooper) && !$isManualApproval && !$isManualRejection)
            {
                return response('Forbidden', 403);
            }

            $is_full = $event_trooper->is_handler
                ? $event_shift->handlersMaxed()
                : $event_shift->troopersMaxed();

            $valid_data = ['status' => $requestedStatus];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
            $this->bus->send($event_trooper_cmd);

            if ($isManualApproval)
            {
                $event_trooper->trooper->notify(new ManualSelectionApprovedNotification($event_trooper, $authTrooper));
            }

            if ($isManualRejection)
            {
                $event_trooper->trooper->notify(new ManualSelectionStandByNotification($event_trooper, $authTrooper));
            }

            if ($is_full && $event_trooper->status === EventTrooperStatus::CANCELLED)
            {
                $next_in_line_cmd = new PromoteNextInLineEventTrooperCommand($event_trooper);
                $this->bus->send($next_in_line_cmd);
            }

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
        elseif ($request->has('costume_id'))
        {
            $valid_data = [
                EventTrooper::COSTUME_ID => $request->validated('costume_id'),
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
            $this->bus->send($event_trooper_cmd);
        }
        elseif ($request->has('backup_costume_id'))
        {
            $valid_data = [
                EventTrooper::BACKUP_COSTUME_ID => $request->validated('backup_costume_id'),
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
            $this->bus->send($event_trooper_cmd);
        }

        return response('ok', 200);
    }
}
