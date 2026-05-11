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
use App\Models\Costume;
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
                $event_trooper->trooper->notify(new ManualSelectionApprovedNotification($event_trooper, $authTrooper));
            }

            if ($isManualRejection)
            {
                $event_trooper->trooper->notify(new ManualSelectionStandByNotification($event_trooper, $authTrooper));
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
            $costume_id = $request->validated('costume_id');
            $previous_is_handler = $event_trooper->is_handler;
            $was_going = $event_trooper->status === EventTrooperStatus::GOING;
            $event_shift = $event_trooper->event_shift;
            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event_shift->event);

            $is_handler = false;
            if ($costume_id !== null)
            {
                $costume = Costume::find($costume_id);
                $is_handler = $costume && in_array($costume->name, [Costume::HANDLER, Costume::COMMAND_STAFF]);
            }
            $handler_changed = $previous_is_handler !== $is_handler;

            // Capture new-pool capacity state BEFORE saving so this trooper isn't counted in it yet
            $new_pool_was_maxed = $is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed();
            $new_pool_org_was_maxed = $effective_org_id !== null
                && $event_shift->orgTroopersMaxed($effective_org_id, $is_handler);

            $this->bus->send(new UpdateEventTrooperCommand($event_trooper, [
                EventTrooper::COSTUME_ID => $costume_id,
                EventTrooper::IS_HANDLER => $is_handler,
            ]));

            if ($handler_changed && $was_going)
            {
                // Trooper vacated their old capacity pool — promote the next in that pool
                $this->bus->send(new PromoteNextInLineEventTrooperCommand(
                    $event_trooper,
                    true,
                    $effective_org_id,
                    $previous_is_handler,
                ));

                // New pool was already full before this trooper joined → demote to stand-by
                if ($new_pool_was_maxed || $new_pool_org_was_maxed)
                {
                    $this->bus->send(new UpdateEventTrooperCommand($event_trooper, [
                        EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                    ]));
                }
            }

            $auth_trooper = Auth::user();
            $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $auth_trooper);
            $event_shift = $this->bus->send($event_shift_query);
            $event = $event_shift->event;
            $can_moderate = $auth_trooper->isModeratorForOrganization($event->organization);
            $count_of_shifts = $event->event_shifts()->count();
            $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');
            $data['open'] = true;

            return response()->view('pages.events.inc.shift-container', $data);
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
