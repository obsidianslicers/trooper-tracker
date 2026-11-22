<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Models\EventGuest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A guest (no tracker account) on a shift's roster. Whoever added them may cancel them or sign
 * them up again; moderators of manual-selection events approve or reject them.
 *
 * @mixin EventGuest
 */
class EventDisplayGuestRow extends JsonResource
{
    public function __construct(EventGuest $event_guest, private readonly EventDisplayContext $context)
    {
        parent::__construct($event_guest);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->context->event;
        $is_manual = $event->status === EventStatus::MANUAL_SELECTION;
        $is_cancelled = $this->status === EventGuestStatus::CANCELLED;
        $can_edit = $this->event_shift->is_open && $this->canUpdateStatus($this->context->viewer);
        $can_pick = $this->context->can_update && $is_manual && $this->event_shift->is_open
            && in_array($this->status, [EventGuestStatus::STAND_BY, EventGuestStatus::GOING], true);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'added_by' => $this->added_by_trooper_id > 0 ? $this->added_by_trooper?->display_name : null,
            'status' => EventDisplayStatus::of($this->status),
            'update_url' => route('events.guest-update', ['event_guest' => $this->resource]),
            'approve' => $can_pick && $this->status === EventGuestStatus::STAND_BY,
            'reject' => $can_pick && $this->status === EventGuestStatus::GOING,
            //  manual-selection events put a returning guest on stand-by for command staff to pick
            'sign_up_again' => !$can_pick && $can_edit && $is_cancelled
                ? ($is_manual ? EventGuestStatus::STAND_BY->value : EventGuestStatus::GOING->value)
                : null,
            'can_cancel' => $can_edit && !$is_cancelled,
            'cancel_confirm' => __("events.Cancel :name's sign-up for this shift?", ['name' => $this->name]),
            'approved_by' => $is_manual && $this->status === EventGuestStatus::GOING && $this->updated_by !== null
                ? $this->approvedBy() : null,
        ];
    }

    private function approvedBy(): string
    {
        return $this->updated_at
            ? __('events.Approved by :name on :date', [
                'name' => $this->updated_by->display_name,
                'date' => $this->updated_at->localFormat('M j, Y g:ia'),
            ])
            : __('events.Approved by :name', ['name' => $this->updated_by->display_name]);
    }
}
