<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\FlashType;
use App\Enums\RosterAction;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Http\Requests\Events\SignUpUpdateRequest;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Messages\Events\Commands\ChangeEventTrooperStation;
use App\Messages\Events\Commands\PromoteNextInLineEventTrooper;
use App\Messages\Events\Commands\UpdateEventTrooper;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use App\Services\EventRosterCapacityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Changes a sign-up from the event page: its plans, club, station, costume, or cancelling
 * and signing up again. Each change goes back to the page with a toast.
 */
class UpdateTrooperSignUpController extends PageController
{
    use AnswersWithEventPage;

    public function __invoke(
        SignUpUpdateRequest $request,
        EventTrooper $event_trooper,
        EventRosterCapacityService $capacity,
    ): RedirectResponse|Response {
        try
        {
            $request->validateInputs();
        }
        catch (ValidationException $exception)
        {
            //  the page's pickers save one at a time, so the message is a toast, not field errors
            return $this->backToEvent(
                $event_trooper->event_shift->event,
                collect($exception->errors())->flatten()->first(),
                FlashType::DANGER,
            );
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

            //  troopers may only pick what the status dropdown offers them; attendance is
            //  confirmed through the signed links once a shift closes, and moderators keep
            //  the wider set they need for manual selection
            $isOfferedStatus = array_key_exists($requestedStatus->value, $event->signUpStatusOptions())
                || $requestedStatus === $event_trooper->status;

            if (!$canModerateEvent && !$isOfferedStatus)
            {
                return $this->backToEvent(
                    $event,
                    __('events.That status is not available for this event.'),
                    FlashType::DANGER,
                );
            }

            if (
                $requestedStatus === EventTrooperStatus::GOING
                && $event_trooper->event_shift_station_id !== null
                && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id)
            ) {
                return $this->backToEvent($event, __('events.Station is full'), FlashType::DANGER);
            }

            $isCancelFromStandBy = $requestedStatus === EventTrooperStatus::CANCELLED
                && $event_trooper->canCancel($authTrooper);

            if (!$event_trooper->canUpdateStatus($authTrooper) && !$isManualApproval && !$isManualRejection && !$isCancelFromStandBy)
            {
                return response('Forbidden', 403);
            }

            if ($isManualApproval && $event_trooper->needsCostumeBeforeGoing())
            {
                return $this->backToEvent(
                    $event,
                    __('events.Trooper must select a costume before being approved'),
                    FlashType::DANGER,
                );
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

            UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

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
                PromoteNextInLineEventTrooper::call(
                    event_trooper: $event_trooper,
                    global_was_full: $is_global_full,
                    effective_org_id: $effective_org_id,
                    event_shift_station_id: $event_trooper->event_shift_station_id,
                );
            }

            $saved = match (true)
            {
                $requestedStatus === EventTrooperStatus::CANCELLED => __('events.Sign-up cancelled.'),
                $previous_status === EventTrooperStatus::CANCELLED => __('events.Signed up again.'),
                default => __('events.Saved.'),
            };

            return $this->saved($event_shift, $event_trooper, $saved);
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
                return $this->backToEvent(
                    $event_shift->event,
                    __('events.That organization is already at capacity.'),
                    FlashType::DANGER,
                );
            }

            $valid_data = [
                EventTrooper::ORGANIZATION_ID => $new_org_id,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ];

            UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

            return $this->saved($event_shift, $event_trooper, __('events.Saved.'));
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

            ChangeEventTrooperStation::call(
                event_trooper: $event_trooper,
                event_shift_station_id: $new_station_id,
            );

            return $this->saved($event_shift, $event_trooper, __('events.Saved.'));
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

            UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

            dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::RESIGNED_UP));

            return $this->saved($event_shift, $event_trooper, __('events.Signed up again.'));
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

            UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

            if ($handler_changed && $was_going)
            {
                // Trooper vacated their old capacity pool — promote the next in that pool
                PromoteNextInLineEventTrooper::call(
                    event_trooper: $event_trooper,
                    global_was_full: true,
                    effective_org_id: $effective_org_id,
                    override_is_handler: $previous_is_handler,
                );

                // New pool was already full before this trooper joined → demote to stand-by
                if ($new_station_was_maxed || $new_pool_was_maxed || $new_pool_org_was_maxed)
                {
                    UpdateEventTrooper::call(
                        event_trooper: $event_trooper,
                        valid_data: [
                            EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                        ],
                    );
                }
            }

            $this->resolveCostumeDecision($event_trooper, $previous_status, $capacity);

            return $this->saved($event_shift, $event_trooper, __('events.Saved.'));
        }
        elseif ($request->has('backup_costume_id'))
        {
            $valid_data = [
                EventTrooper::BACKUP_COSTUME_ID => $request->validated('backup_costume_id'),
            ];

            UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

            return $this->saved($event_trooper->event_shift, $event_trooper, __('events.Saved.'));
        }

        return $this->backToEvent($event_trooper->event_shift->event);
    }

    /**
     * A short confirmation, so a change that saves on its own still says it did. When the
     * change moved the sign-up somewhere the trooper didn't pick (stand-by, or still needing
     * a costume), the message says so.
     */
    private function saved(EventShift $event_shift, EventTrooper $event_trooper, string $message): RedirectResponse
    {
        $message = match ($event_trooper->fresh()?->status)
        {
            EventTrooperStatus::STAND_BY => $message.' '.__('events.This sign-up is on the stand-by list.'),
            EventTrooperStatus::PENDING => $message.' '
                .__('events.Select a costume, or mark attending without one, to confirm your spot.'),
            default => $message,
        };

        return $this->backToEvent($event_shift->event, $message);
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
}
