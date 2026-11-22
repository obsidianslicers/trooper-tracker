<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Models\EventGuest;
use App\Models\EventShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Adds guests (people without a tracker account) to a shift from the event page.
 *
 * Creates guest records for non-empty names submitted in the
 * newline-delimited guest_names payload.
 */
class SignUpGuestController extends PageController
{
    use AnswersWithEventPage;

    /**
     * Creates new guest entries for the current trooper and goes back to the event page.
     */
    public function __invoke(Request $request, EventShift $event_shift): RedirectResponse
    {
        $trooper = $request->user();

        $event = $event_shift->event;

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        if ($event->require_mission_brief_ack && !$event->hasMissionBriefAcknowledgementFor($trooper))
        {
            return $this->backToEvent($event);
        }

        $grace_period_moderator_add = $can_moderate && $event->is_within_grace_period;

        if (!$event_shift->is_open && !$grace_period_moderator_add)
        {
            return $this->backToEvent($event);
        }

        if (!$can_moderate && !$event_shift->isGoing($trooper))
        {
            return $this->backToEvent($event);
        }

        $guestStatus = $event->status === EventStatus::MANUAL_SELECTION
            ? EventGuestStatus::STAND_BY
            : EventGuestStatus::GOING;

        $guest_names = $request->input('guest_names');

        $guest_names = preg_split('/\\r\\n|\\r|\\n|\\\\n/', (string) $guest_names) ?: [];

        foreach ($guest_names as $guest_name)
        {
            $guest_name = trim($guest_name);

            if (!empty($guest_name))
            {
                $event_shift->event_guests()->firstOrCreate(
                    [EventGuest::NAME => $guest_name],
                    [
                        EventGuest::ADDED_BY_TROOPER_ID => $trooper->id,
                        EventGuest::STATUS => $guestStatus,
                    ]
                );
            }
        }

        return $this->backToEvent($event, __('events.Guest added.'));
    }
}
