<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Trooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Adds guests to a shift on behalf of a trooper going to it (or command staff for the event).
 * A guest already on the shift by that name is left as is.
 *
 * @method static bool call(EventShift $event_shift, Trooper $added_by_trooper, array $guest_names)
 */
final class SignUpEventGuests extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  array<int, string>  $guest_names
     */
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly Trooper $added_by_trooper,
        private readonly array $guest_names,
    ) {}

    /**
     * @return bool Whether the trooper may add guests to this shift.
     */
    public function handle(): bool
    {
        if (!$this->mayAddGuests())
        {
            return false;
        }

        $status = $this->event_shift->event->status === EventStatus::MANUAL_SELECTION
            ? EventGuestStatus::STAND_BY
            : EventGuestStatus::GOING;

        foreach ($this->guest_names as $guest_name)
        {
            $guest_name = trim($guest_name);

            if (!empty($guest_name))
            {
                $this->event_shift->event_guests()->firstOrCreate(
                    [EventGuest::NAME => $guest_name],
                    [
                        EventGuest::ADDED_BY_TROOPER_ID => $this->added_by_trooper->id,
                        EventGuest::STATUS => $status,
                    ]
                );
            }
        }

        return true;
    }

    private function mayAddGuests(): bool
    {
        $trooper = $this->added_by_trooper;
        $event = $this->event_shift->event;
        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        if ($event->require_mission_brief_ack && !$event->hasMissionBriefAcknowledgementFor($trooper))
        {
            return false;
        }

        if (!$this->event_shift->is_open && !($can_moderate && $event->is_within_grace_period))
        {
            return false;
        }

        return $can_moderate || $this->event_shift->isGoing($trooper);
    }
}
