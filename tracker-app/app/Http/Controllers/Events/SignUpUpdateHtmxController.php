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
use App\Mail\Events\TrooperManualSelectionApproved;
use App\Mail\Events\TrooperManualSelectionStandBy;
use App\Models\EventTrooper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Handles HTMX-driven updates to event trooper sign-up details.
 *
 * This controller processes updates to a trooper's event participation,
 * including status changes (going, cancelled, stand-by) and costume selection.
 * When a trooper cancels from a full event, it automatically promotes the next
 * stand-by trooper to attending status.
 */
class SignUpUpdateHtmxController extends MagicBusController
{
    /**
     * Handle the incoming HTMX request to update event trooper status or costume
     *
     * Processes two types of updates:
     * - Status changes: Updates the trooper's attendance status and handles waitlist promotion
     * - Costume changes: Updates the trooper's selected costume for the event
     *
     * @param  SignupUpdateHtmxRequest  $request  The validated request containing status or costume_id
     * @param  EventTrooper  $event_trooper  The event trooper record to update
     * @return Response HTTP 200 response indicating success
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

            $isCancelFromStandBy = $requestedStatus === EventTrooperStatus::CANCELLED
                && $event_trooper->canCancel($event_shift, $authTrooper);

            if (!$event_trooper->canUpdateStatus($event_shift, $authTrooper) && !$isManualApproval && !$isManualRejection && !$isCancelFromStandBy)
            {
                return response('Forbidden', 403);
            }

            $previous_status = $event_trooper->status;

            $is_global_full = $event_trooper->is_handler
                ? $event_shift->handlersMaxed()
                : $event_shift->troopersMaxed();

            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event_shift->event);

            $org_was_full = $effective_org_id !== null
                && $event_shift->orgTroopersMaxed($effective_org_id, $event_trooper->is_handler);

            $valid_data = ['status' => $requestedStatus];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);

            if ($isManualApproval)
            {
                Mail::to($event_trooper->trooper->email)->queue(new TrooperManualSelectionApproved($event_trooper, $authTrooper));
            }

            if ($isManualRejection)
            {
                Mail::to($event_trooper->trooper->email)->queue(new TrooperManualSelectionStandBy($event_trooper, $authTrooper));
            }

            $going_to_not_going = $previous_status === EventTrooperStatus::GOING
                && $requestedStatus !== EventTrooperStatus::GOING
                && !$isManualSelectionEvent;

            if ($going_to_not_going && ($is_global_full || $org_was_full))
            {
                $this->bus->send(new PromoteNextInLineEventTrooperCommand($event_trooper, $is_global_full, $effective_org_id));
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
        elseif ($request->has('organization_id'))
        {
            $event_shift = $event_trooper->event_shift;
            $auth_trooper = Auth::user();

            if (!$event_trooper->canUpdateCostume($event_shift, $auth_trooper))
            {
                return response('Forbidden', 403);
            }

            $new_org_id = $request->validated('organization_id') ? (int) $request->validated('organization_id') : null;

            if (
                $new_org_id !== null
                && $event_trooper->status === EventTrooperStatus::GOING
                && $event_shift->orgTroopersMaxed($new_org_id, $event_trooper->is_handler)
            ) {
                $message = json_encode([
                    'message' => 'That organization is already at capacity.',
                    'type' => 'danger',
                    'focus' => true,
                    'fadeOut' => 5000,
                ]);

                $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $auth_trooper);
                $event_shift = $this->bus->send($event_shift_query);
                $event = $event_shift->event;
                $can_moderate = $auth_trooper->isModeratorForOrganization($event->organization);
                $count_of_shifts = $event->event_shifts()->count();
                $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');
                $data['open'] = true;

                return response()->view('pages.events.inc.shift-container', $data)
                    ->header('X-Flash-Message', $message);
            }

            $valid_data = [
                EventTrooper::ORGANIZATION_ID => $new_org_id,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
            $this->bus->send($event_trooper_cmd);

            $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $auth_trooper);
            $event_shift = $this->bus->send($event_shift_query);
            $event = $event_shift->event;
            $can_moderate = $auth_trooper->isModeratorForOrganization($event->organization);
            $count_of_shifts = $event->event_shifts()->count();
            $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');
            $data['open'] = true;

            return response()->view('pages.events.inc.shift-container', $data);
        }
        elseif ($request->has('resign_up'))
        {
            $event_shift = $event_trooper->event_shift;
            $event = $event_shift->event;
            $authTrooper = Auth::user();

            if (!$event_trooper->canReSignUp($event_shift, $authTrooper))
            {
                return response('Forbidden', 403);
            }

            $is_handler = $event_trooper->is_handler;
            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event);

            $global_maxed = $is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed();
            $org_maxed = $effective_org_id !== null && $event_shift->orgTroopersMaxed($effective_org_id, $is_handler);

            $new_status = ($global_maxed || $org_maxed)
                ? EventTrooperStatus::STAND_BY
                : EventTrooperStatus::GOING;

            $valid_data = [
                EventTrooper::STATUS => $new_status,
                EventTrooper::SIGNED_UP_AT => now(),
            ];

            $this->bus->send(new UpdateEventTrooperCommand($event_trooper, $valid_data));

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
            //  costume organization ids handled via observer, so we
            //  only need to update the costume_id on the event trooper record here
            $valid_data = [
                EventTrooper::COSTUME_ID => $request->validated('costume_id'),
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);
        }
        elseif ($request->has('backup_costume_id'))
        {
            //  backup costume organization ids handled via observer, so we
            //  only need to update the backup_costume_id on the event trooper record here
            $valid_data = [
                EventTrooper::BACKUP_COSTUME_ID => $request->validated('backup_costume_id'),
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);
        }

        return response('ok', 200);
    }
}
