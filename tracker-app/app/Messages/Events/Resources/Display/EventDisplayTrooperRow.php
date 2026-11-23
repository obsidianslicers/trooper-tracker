<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of a shift's roster on the event page: who, their costume, their status, the buttons
 * worth having right on the row, and, when the viewer may change it, what its Edit area offers.
 *
 * @mixin EventTrooper
 */
class EventDisplayTrooperRow extends JsonResource
{
    public function __construct(
        EventTrooper $event_trooper,
        private readonly EventDisplayContext $context,
        private readonly ?int $roster_number,
    ) {
        parent::__construct($event_trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $this->context->viewer;
        $editor = $this->editor();

        return [
            'id' => $this->id,
            'number' => $this->roster_number,
            'trooper' => [
                'id' => $this->trooper->id,
                'name' => $this->trooper->display_name,
                'legal_name' => $this->trooper->legal_name,
                'url' => route('service-records.trooper', ['trooper' => $this->trooper->id]),
            ],
            'is_mine' => $this->trooper_id === $viewer->id,
            'needs_verification' => $this->context->can_moderate && (bool) $this->trooper->is_untrusted,
            'added_by' => $this->addedBy(),
            'status' => EventDisplayStatus::of($this->status),
            'costume' => $this->costumeShown(),
            'station' => $this->event_shift_station?->name,
            'approved_by' => $this->approvedBy(),
            'actions' => $this->actions(),
            'editor' => $editor,
            'opens_itself' => $editor !== null && $this->status === EventTrooperStatus::PENDING
                && ($this->trooper_id === $viewer->id || $this->added_by_trooper_id === $viewer->id),
        ];
    }

    private function addedBy(): ?string
    {
        $is_someone_else = $this->added_by_trooper_id > 0
            && $this->added_by_trooper_id !== $this->trooper_id
            && $this->added_by_trooper !== null;

        return $is_someone_else ? $this->added_by_trooper->display_name : null;
    }

    /**
     * What was picked, as the row shows it when it isn't being edited.
     *
     * @return array<string, mixed>|null
     */
    private function costumeShown(): ?array
    {
        if (!$this->context->event->requiresCostume())
        {
            return null;
        }

        return [
            'name' => $this->costume?->name,
            'organizations' => $this->costume !== null ? ($this->costume_organizations ?: null) : null,
            'is_handler' => $this->costume === null && (bool) $this->is_handler,
            'is_without_costume' => $this->costume === null && (bool) $this->is_attending_without_costume,
            'backup' => $this->backup_costume?->name,
        ];
    }

    private function approvedBy(): ?string
    {
        if (
            $this->context->event->status !== EventStatus::MANUAL_SELECTION
            || $this->status !== EventTrooperStatus::GOING
            || $this->updated_by === null
        ) {
            return null;
        }

        return $this->updated_at
            ? __('events.Approved by :name on :date', [
                'name' => $this->updated_by->display_name,
                'date' => $this->updated_at->localFormat('M j, Y g:ia'),
            ])
            : __('events.Approved by :name', ['name' => $this->updated_by->display_name]);
    }

    /**
     * The row's own buttons: Approve / Reject for moderators of manual-selection events, Sign up
     * again once cancelled, and Made It / Missed It after the troop.
     *
     * @return array<string, mixed>
     */
    private function actions(): array
    {
        $shift = $this->eventShift();
        $status = $this->status;
        $is_manual = $this->context->can_moderate
            && $this->context->event->status === EventStatus::MANUAL_SELECTION
            && $shift->is_open
            && in_array($status, [EventTrooperStatus::STAND_BY, EventTrooperStatus::GOING], true);

        return [
            'update_url' => route('events.signup-update', ['event_trooper' => $this->resource]),
            'approve' => $is_manual && $status === EventTrooperStatus::STAND_BY && !$this->needsCostumeBeforeGoing(),
            'reject' => $is_manual && $status === EventTrooperStatus::GOING,
            //  shown as "Needs Costume" until they pick one
            'needs_costume_to_approve' => $is_manual && $status === EventTrooperStatus::STAND_BY
                && $this->needsCostumeBeforeGoing()
                ? EventDisplayStatus::of(EventTrooperStatus::PENDING) : null,
            'sign_up_again' => !$is_manual ? $this->signUpAgain() : null,
            'attendance' => !$is_manual ? $this->attendance() : null,
        ];
    }

    /**
     * 'resign_up' re-queues them by sign-up time on limited events; 'going' just sets them back.
     */
    private function signUpAgain(): ?string
    {
        if (!$this->eventShift()->is_open || $this->status !== EventTrooperStatus::CANCELLED)
        {
            return null;
        }

        $viewer = $this->context->viewer;

        if ($this->context->event->hasLimits())
        {
            return $this->canReSignUp($viewer) ? 'resign_up' : null;
        }

        return $this->canUpdateStatus($viewer) ? 'going' : null;
    }

    /**
     * @return array{made_it_url: string|null, missed_it_url: string|null}|null
     */
    private function attendance(): ?array
    {
        $shift = $this->eventShift();
        $viewer = $this->context->viewer;

        $is_after = $shift->is_closed
            && $shift->shift_ends_at->isPast()
            && $this->isEligibleForAttendance()
            && ($this->context->can_moderate || $this->canMarkAttendance($viewer));

        if (!$is_after)
        {
            return null;
        }

        $answered = in_array($this->status, [
            EventTrooperStatus::ATTENDED,
            EventTrooperStatus::UNABLE_TO_ATTEND,
            EventTrooperStatus::AWAITING_VERIFICATION,
        ], true);

        return $answered ? null : [
            'made_it_url' => $this->getAttendanceUrl(EventTrooperStatus::ATTENDED),
            'missed_it_url' => $this->getAttendanceUrl(EventTrooperStatus::UNABLE_TO_ATTEND),
        ];
    }

    /**
     * The Edit area: pickers for what may change, plans, and Cancel. Null when nothing may.
     *
     * @return array<string, mixed>|null
     */
    private function editor(): ?array
    {
        $viewer = $this->context->viewer;
        $can_costume = $this->canUpdateCostume($viewer);
        $can_cancel = $this->canCancel($viewer);

        if ($this->status === EventTrooperStatus::CANCELLED || (!$can_costume && !$can_cancel))
        {
            return null;
        }

        return [
            'stations' => $can_costume ? $this->stationPicker() : null,
            'organizations' => $can_costume ? $this->organizationPicker() : null,
            'costumes' => $can_costume ? $this->costumePicker() : null,
            //  costumes is also null when the viewer may not change it; this says which
            'requires_costume' => $this->context->event->requiresCostume(),
            'plans' => $this->plans(),
            'can_cancel' => $this->eventShift()->is_open && $can_cancel,
            'cancel_confirm' => $this->trooper_id === $viewer->id
                ? __('events.Cancel your sign-up for this shift?')
                : __("events.Cancel :name's sign-up for this shift?", ['name' => $this->trooper->display_name]),
        ];
    }

    /**
     * @return array{value: int|null, options: list<array{value: int, label: string}>}|null
     */
    private function stationPicker(): ?array
    {
        $shift = $this->eventShift();

        if (!$shift->usesStations())
        {
            return null;
        }

        return [
            'value' => $this->event_shift_station_id,
            'options' => $shift->event_shift_stations
                ->map(fn (EventShiftStation $s) => ['value' => $s->id, 'label' => $s->name])
                ->values()
                ->all(),
        ];
    }

    /**
     * A picker when they could troop for more than one club with its own limits, or the one
     * club they'll troop as.
     *
     * @return array<string, mixed>|null
     */
    private function organizationPicker(): ?array
    {
        $event = $this->context->event;
        $eligible = $this->trooper->eligibleOrgsForEvent($event);
        $limited_ids = $event->event_organizations
            ->filter(fn ($o) => $o->troopers_allowed !== null || $o->handlers_allowed !== null)
            ->pluck('organization_id')
            ->all();

        if ($eligible->whereIn('id', $limited_ids)->isEmpty())
        {
            return null;
        }

        return [
            'value' => $this->organization_id,
            'options' => $eligible->map(fn ($o) => ['value' => $o->id, 'label' => $o->name])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function costumePicker(): ?array
    {
        if (!$this->context->event->requiresCostume())
        {
            return null;
        }

        $options = collect($this->costumes ?? [])
            ->map(fn (string $name, int $id) => ['value' => (string) $id, 'label' => $name])
            ->values()
            ->all();

        return [
            'value' => $this->is_attending_without_costume
                ? 'none' : ($this->costume_id !== null ? (string) $this->costume_id : ''),
            'backup_value' => $this->backup_costume_id !== null ? (string) $this->backup_costume_id : '',
            'options' => $options,
        ];
    }

    /**
     * Going / Tentative, where the event offers both and the viewer may change them.
     *
     * @return array{value: string, options: list<array{value: string, label: string}>}|null
     */
    private function plans(): ?array
    {
        $options = $this->context->event->planStatusOptions($this->status);
        $can_change = $this->eventShift()->is_open
            && in_array($this->status, EventTrooperStatus::intentToGoArray(), true)
            && $this->canCancel($this->context->viewer)
            && $this->canUpdateStatus($this->context->viewer)
            && count($options) > 1;

        if (!$can_change)
        {
            return null;
        }

        return [
            'value' => $this->status->value,
            'options' => collect($options)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    private function eventShift(): EventShift
    {
        return $this->event_shift;
    }
}
