<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\RosterAction;
use App\Enums\SignUpChangeOutcome;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Changes the status of a trooper's sign-up, from the roster's status picker.
 *
 * Troopers pick from the statuses the event offers them; command staff for the event can also
 * approve stand-by troopers on a manual-selection event, or send them back to stand-by. When a
 * going trooper leaves a full shift, organization or station, the next in line moves up.
 *
 * @method static SignUpChangeOutcome call(EventTrooper $event_trooper, EventTrooperStatus $status, Trooper $changed_by_trooper)
 */
final class ChangeEventTrooperStatus extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly EventTrooperStatus $status,
        private readonly Trooper $changed_by_trooper,
    ) {}

    public function handle(): SignUpChangeOutcome
    {
        $is_manual_approval = $this->isManualApproval();
        $is_manual_rejection = $this->isManualRejection();
        $refusal = $this->refusal($is_manual_approval || $is_manual_rejection, $is_manual_approval);

        if ($refusal !== null)
        {
            return $refusal;
        }

        $status = $this->status === EventTrooperStatus::GOING && $this->event_trooper->needsCostumeBeforeGoing()
            ? EventTrooperStatus::PENDING
            : $this->status;

        $previous_status = $this->event_trooper->status;
        $was_full = $this->fullness();

        UpdateEventTrooper::call(event_trooper: $this->event_trooper, valid_data: ['status' => $status]);

        $this->notify($status, $previous_status, $is_manual_approval, $is_manual_rejection);

        $going_to_not_going = $previous_status === EventTrooperStatus::GOING
            && $status !== EventTrooperStatus::GOING
            && !$this->isManualSelectionEvent();

        if ($going_to_not_going && in_array(true, $was_full, true))
        {
            PromoteNextInLineEventTrooper::call(
                event_trooper: $this->event_trooper,
                global_was_full: $was_full['global'],
                effective_org_id: $this->effectiveOrgId(),
                event_shift_station_id: $this->event_trooper->event_shift_station_id,
            );
        }

        return SignUpChangeOutcome::SAVED;
    }

    private function refusal(bool $is_manual_decision, bool $is_manual_approval): ?SignUpChangeOutcome
    {
        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;
        $is_going = $this->status === EventTrooperStatus::GOING;

        if ($this->isManualSelectionEvent() && $is_going && !$this->canModerateEvent())
        {
            return SignUpChangeOutcome::FORBIDDEN;
        }

        //  troopers may only pick what the status dropdown offers them; attendance is confirmed
        //  through the signed links once a shift closes, and moderators keep the wider set they
        //  need for manual selection
        $is_offered = array_key_exists($this->status->value, $event_shift->event->signUpStatusOptions())
            || $this->status === $event_trooper->status;

        if (!$this->canModerateEvent() && !$is_offered)
        {
            return SignUpChangeOutcome::STATUS_NOT_OFFERED;
        }

        if ($is_going && $event_trooper->event_shift_station_id !== null
            && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id))
        {
            return SignUpChangeOutcome::STATION_FULL;
        }

        return $this->permissionRefusal($is_manual_decision, $is_manual_approval);
    }

    private function permissionRefusal(bool $is_manual_decision, bool $is_manual_approval): ?SignUpChangeOutcome
    {
        $is_cancel_from_stand_by = $this->status === EventTrooperStatus::CANCELLED
            && $this->event_trooper->canCancel($this->changed_by_trooper);

        if (!$this->event_trooper->canUpdateStatus($this->changed_by_trooper)
            && !$is_manual_decision
            && !$is_cancel_from_stand_by)
        {
            return SignUpChangeOutcome::FORBIDDEN;
        }

        if ($is_manual_approval && $this->event_trooper->needsCostumeBeforeGoing())
        {
            return SignUpChangeOutcome::COSTUME_REQUIRED;
        }

        return null;
    }

    /**
     * Whether the shift, the trooper's organization and their station were full before the change.
     *
     * @return array{global: bool, organization: bool, station: bool}
     */
    private function fullness(): array
    {
        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;
        $effective_org_id = $this->effectiveOrgId();

        return [
            'global' => $event_trooper->is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed(),
            'organization' => $effective_org_id !== null
                && $event_shift->orgTroopersMaxed($effective_org_id, $event_trooper->is_handler),
            'station' => $event_trooper->event_shift_station_id !== null
                && $event_shift->stationMaxed($event_trooper->event_shift_station_id),
        ];
    }

    private function notify(
        EventTrooperStatus $status,
        EventTrooperStatus $previous_status,
        bool $is_manual_approval,
        bool $is_manual_rejection,
    ): void {
        $event_trooper = $this->event_trooper;

        if ($status === EventTrooperStatus::CANCELLED && $previous_status !== EventTrooperStatus::CANCELLED)
        {
            dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::CANCELLED));
        }

        if ($is_manual_approval)
        {
            $event_trooper->trooper->notify(
                new ManualSelectionApprovedNotification($event_trooper, $this->changed_by_trooper)
            );
        }

        if ($is_manual_rejection)
        {
            $event_trooper->trooper->notify(
                new ManualSelectionStandByNotification($event_trooper, $this->changed_by_trooper)
            );
        }
    }

    private function isManualApproval(): bool
    {
        return $this->isManualSelectionEvent()
            && $this->canModerateEvent()
            && $this->event_trooper->status === EventTrooperStatus::STAND_BY
            && $this->status === EventTrooperStatus::GOING;
    }

    private function isManualRejection(): bool
    {
        return $this->isManualSelectionEvent()
            && $this->canModerateEvent()
            && $this->event_trooper->intendsToGo()
            && $this->status === EventTrooperStatus::STAND_BY;
    }

    private function isManualSelectionEvent(): bool
    {
        return $this->event_trooper->event_shift->event->status === EventStatus::MANUAL_SELECTION;
    }

    private function canModerateEvent(): bool
    {
        return $this->changed_by_trooper->can('update', $this->event_trooper->event_shift->event);
    }

    private function effectiveOrgId(): ?int
    {
        return $this->event_trooper->organization_id
            ?? $this->event_trooper->effectiveOrgId($this->event_trooper->event_shift->event);
    }
}
