<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\RosterAction;
use App\Features\Events\Commands\ChangeEventTrooperStationCommand;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\SignupUpdateHtmxRequest;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use App\Services\EventRosterCapacityService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Handles HTMX-driven updates to event trooper sign-up details.
 */
class SignUpUpdateHtmxController extends MagicBusController
{
    public function __invoke(
        SignupUpdateHtmxRequest $request,
        EventTrooper $event_trooper,
        EventRosterCapacityService $capacity,
    ): Response {
        try
        {
            $request->validateInputs();
        }
        catch (ValidationException $exception)
        {
            //  a redirect with session errors would re-render every row's
            //  identically-named inputs as errored and cleared; re-render the
            //  shift container as-is and surface the message as a flash
            return $this->shiftContainerView($event_trooper->event_shift, Auth::user())
                ->header('X-Flash-Message', json_encode([
                    'message' => collect($exception->errors())->flatten()->first(),
                    'type' => 'danger',
                    'focus' => true,
                    'fadeOut' => 5000,
                ]));
        }

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
                && $event_trooper->intendsToGo()
                && $requestedStatus === EventTrooperStatus::STAND_BY;

            if ($isManualSelectionEvent && $requestedStatus === EventTrooperStatus::GOING && !$canModerateEvent)
            {
                return response('Forbidden', 403);
            }

            if (
                $requestedStatus === EventTrooperStatus::GOING
                && $event_trooper->event_shift_station_id !== null
                && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id)
            ) {
                return response('Station is full', 409);
            }

            $isCancelFromStandBy = $requestedStatus === EventTrooperStatus::CANCELLED
                && $event_trooper->canCancel($authTrooper);

            if (!$event_trooper->canUpdateStatus($authTrooper) && !$isManualApproval && !$isManualRejection && !$isCancelFromStandBy)
            {
                return response('Forbidden', 403);
            }

            if ($isManualApproval && $event_trooper->needsCostumeBeforeGoing())
            {
                return response('Trooper must select a costume before being approved', 409);
            }

            if ($requestedStatus === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
            {
                $requestedStatus = EventTrooperStatus::PENDING;
            }

            $previous_status = $event_trooper->status;

            $is_global_full = $event_trooper->is_handler
                ? $event_shift->handlersMaxed()
                : $event_shift->troopersMaxed();

            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event_shift->event);

            $org_was_full = $effective_org_id !== null
                && $event_shift->orgTroopersMaxed($effective_org_id, $event_trooper->is_handler);
            $station_was_full = $event_trooper->event_shift_station_id !== null
                && $event_shift->stationMaxed($event_trooper->event_shift_station_id);

            $valid_data = ['status' => $requestedStatus];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
            $this->bus->send($event_trooper_cmd);

            if ($requestedStatus === EventTrooperStatus::CANCELLED && $previous_status !== EventTrooperStatus::CANCELLED)
            {
                dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::CANCELLED));
            }

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

            if ($going_to_not_going && ($is_global_full || $org_was_full || $station_was_full))
            {
                $this->bus->send(new PromoteNextInLineEventTrooperCommand(
                    $event_trooper,
                    $is_global_full,
                    $effective_org_id,
                    event_shift_station_id: $event_trooper->event_shift_station_id,
                ));
            }

            return $this->shiftContainerView($event_shift, $authTrooper);
        }
        elseif ($request->has('organization_id'))
        {
            $event_shift = $event_trooper->event_shift;
            $auth_trooper = Auth::user();

            if (!$event_trooper->canUpdateCostume($auth_trooper))
            {
                return response('Forbidden', 403);
            }

            $new_org_id = $request->validated('organization_id') ? (int) $request->validated('organization_id') : null;

            if (
                $new_org_id !== null
                && $event_trooper->intendsToGo()
                && $event_shift->orgTroopersMaxed($new_org_id, $event_trooper->is_handler)
            ) {
                $message = json_encode([
                    'message' => 'That organization is already at capacity.',
                    'type' => 'danger',
                    'focus' => true,
                    'fadeOut' => 5000,
                ]);

                return $this->shiftContainerView($event_shift, $auth_trooper)
                    ->header('X-Flash-Message', $message);
            }

            $valid_data = [
                EventTrooper::ORGANIZATION_ID => $new_org_id,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);
            $this->bus->send($event_trooper_cmd);

            return $this->shiftContainerView($event_shift, $auth_trooper);
        }
        elseif ($request->has('event_shift_station_id'))
        {
            $event_shift = $event_trooper->event_shift;
            $auth_trooper = Auth::user();

            if (!$event_trooper->canUpdateCostume($auth_trooper) && !$auth_trooper->can('update', $event_shift->event))
            {
                return response('Forbidden', 403);
            }

            $new_station_id = $request->validated('event_shift_station_id')
                ? (int) $request->validated('event_shift_station_id')
                : null;

            $this->bus->send(new ChangeEventTrooperStationCommand($event_trooper, $new_station_id));

            return $this->shiftContainerView($event_shift, $auth_trooper);
        }
        elseif ($request->has('resign_up'))
        {
            $event_shift = $event_trooper->event_shift;
            $event = $event_shift->event;
            $authTrooper = Auth::user();

            if (!$event_trooper->canReSignUp($authTrooper))
            {
                return response('Forbidden', 403);
            }

            $is_handler = $event_trooper->is_handler;
            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event);

            $station_maxed = $event_trooper->event_shift_station_id !== null
                && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id);
            $global_maxed = $is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed();
            $org_maxed = $effective_org_id !== null && $event_shift->orgTroopersMaxed($effective_org_id, $is_handler);

            $new_status = ($station_maxed || $global_maxed || $org_maxed)
                ? EventTrooperStatus::STAND_BY
                : EventTrooperStatus::GOING;

            if ($new_status === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
            {
                $new_status = EventTrooperStatus::PENDING;
            }

            $valid_data = [
                EventTrooper::STATUS => $new_status,
                EventTrooper::SIGNED_UP_AT => now(),
            ];

            $this->bus->send(new UpdateEventTrooperCommand($event_trooper, $valid_data));

            dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::RESIGNED_UP));

            return $this->shiftContainerView($event_shift, $authTrooper);
        }
        elseif ($request->has('costume_id'))
        {
            $auth_trooper = Auth::user();

            if (!$event_trooper->canUpdateCostume($auth_trooper))
            {
                return response('Forbidden', 403);
            }

            $raw_costume_id = $request->validated('costume_id');
            $attending_without_costume = $raw_costume_id === 'none';
            $costume_id = ($raw_costume_id !== null && !$attending_without_costume) ? (int) $raw_costume_id : null;

            $previous_is_handler = $event_trooper->is_handler;
            $previous_status = $event_trooper->status;
            $was_going = $event_trooper->intendsToGo();
            $event_shift = $event_trooper->event_shift;
            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event_shift->event);

            $costume = $costume_id !== null ? Costume::find($costume_id) : null;
            $is_handler = $costume?->countsAsHandler() ?? false;
            $handler_changed = $previous_is_handler !== $is_handler;

            // Capture new-pool capacity state BEFORE saving so this trooper isn't counted in it yet
            $new_pool_was_maxed = $is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed();
            $new_pool_org_was_maxed = $effective_org_id !== null
                && $event_shift->orgTroopersMaxed($effective_org_id, $is_handler);
            $new_station_was_maxed = $event_trooper->event_shift_station_id !== null
                && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id);

            $org_ids = ($costume !== null && !$costume->countsAsHandler())
                ? $costume->approvedOrgIdsForTrooper($event_trooper->trooper_id)
                : null;

            $valid_data = [
                EventTrooper::COSTUME_ID => $costume_id,
                EventTrooper::IS_HANDLER => $is_handler,
                EventTrooper::COSTUME_ORGANIZATION_IDS => !empty($org_ids) ? $org_ids : null,
                EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => $attending_without_costume,
            ];

            $this->bus->send(new UpdateEventTrooperCommand($event_trooper, $valid_data));

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
                if ($new_station_was_maxed || $new_pool_was_maxed || $new_pool_org_was_maxed)
                {
                    $this->bus->send(new UpdateEventTrooperCommand($event_trooper, [
                        EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                    ]));
                }
            }

            $this->resolveCostumeDecision($event_trooper, $previous_status, $capacity);

            return $this->shiftContainerView($event_shift, $auth_trooper);
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

    /**
     * Reconciles GOING status against the costume decision after a costume
     * or "attending without costume" change.
     *
     * Promotes a PENDING trooper who just made their decision into GOING (or
     * STAND_BY if capacity is gone), and demotes a GOING trooper who lost
     * their decision (e.g. cleared their costume) back to PENDING.
     *
     * On manual-selection events, a decided trooper always lands on STAND_BY
     * instead of GOING — moderator approval is required and must never be
     * bypassed just by making a costume decision.
     */
    private function resolveCostumeDecision(
        EventTrooper $event_trooper,
        EventTrooperStatus $previous_status,
        EventRosterCapacityService $capacity,
    ): void {
        $event_shift = $event_trooper->event_shift;

        if ($previous_status === EventTrooperStatus::PENDING && !$event_trooper->needsCostumeBeforeGoing())
        {
            if ($event_shift->event->status === EventStatus::MANUAL_SELECTION)
            {
                $event_trooper->status = EventTrooperStatus::STAND_BY;
                $event_trooper->save();

                return;
            }

            $effective_org_id = $event_trooper->organization_id
                ?? $event_trooper->effectiveOrgId($event_shift->event);

            $can_go = $capacity->canGo(
                $event_shift,
                $effective_org_id,
                $event_trooper->event_shift_station_id,
                $event_trooper->is_handler,
            );

            $event_trooper->status = $can_go ? EventTrooperStatus::GOING : EventTrooperStatus::STAND_BY;
            $event_trooper->save();

            return;
        }

        if ($previous_status === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
        {
            $event_trooper->status = EventTrooperStatus::PENDING;
            $event_trooper->save();
        }
    }

    /**
     * Render the refreshed shift container partial after a roster change.
     */
    private function shiftContainerView(EventShift $event_shift, Trooper $trooper): Response
    {
        $event_shift = $this->bus->send(new GetEventShiftDisplayQuery($event_shift, $trooper));
        $event = $event_shift->event;

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);
        $count_of_shifts = $event->event_shifts()->count();

        $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');
        $data['open'] = true;

        return response()->view('pages.events.inc.shift-container', $data);
    }
}
