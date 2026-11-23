<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\SignUpChangeOutcome;
use App\Models\EventGuest;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Changes a guest's status on a shift. On a manual-selection event only command staff move a
 * guest between stand-by and going.
 *
 * @method static SignUpChangeOutcome call(EventGuest $event_guest, EventGuestStatus $status, Trooper $changed_by_trooper)
 */
final class ChangeEventGuestStatus extends Message
{
    public function __construct(
        private readonly EventGuest $event_guest,
        private readonly EventGuestStatus $status,
        private readonly Trooper $changed_by_trooper,
    ) {}

    public function handle(): SignUpChangeOutcome
    {
        if (!$this->mayChange())
        {
            return SignUpChangeOutcome::FORBIDDEN;
        }

        UpdateEventGuest::call(event_guest: $this->event_guest, valid_data: ['status' => $this->status]);

        return SignUpChangeOutcome::SAVED;
    }

    private function mayChange(): bool
    {
        $event = $this->event_guest->event_shift->event;
        $can_moderate = $this->changed_by_trooper->can('update', $event);
        $is_manual_selection = $event->status === EventStatus::MANUAL_SELECTION;

        if ($is_manual_selection && $this->status === EventGuestStatus::GOING && !$can_moderate)
        {
            return false;
        }

        $is_manual_decision = $is_manual_selection && $can_moderate && (
            ($this->event_guest->status === EventGuestStatus::STAND_BY && $this->status === EventGuestStatus::GOING)
            || ($this->event_guest->status === EventGuestStatus::GOING && $this->status === EventGuestStatus::STAND_BY)
        );

        return $this->event_guest->canUpdateStatus($this->changed_by_trooper) || $is_manual_decision;
    }
}
