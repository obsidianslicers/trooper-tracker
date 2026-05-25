<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\EventGuest;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles HTMX-driven guest signups for an event shift.
 *
 * Creates guest records for non-empty names submitted in the
 * newline-delimited guest_names payload.
 */
class GuestSignUpHtmxController extends MagicBusController
{
    /**
     * Processes an HTMX guest sign-up request.
     *
     * Creates new guest entries for the current trooper and returns the
     * updated shift container partial with an HX trigger header.
     */
    public function __invoke(Request $request, EventShift $event_shift): Response
    {
        $trooper = $request->user();

        $event = $event_shift->event;

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        if ($event->require_mission_brief_ack && !$event->hasMissionBriefAcknowledgementFor($trooper))
        {
            return $this->shiftContainerResponse($event_shift, $trooper, $can_moderate);
        }

        if (!$event_shift->is_open)
        {
            return $this->shiftContainerResponse($event_shift, $trooper, $can_moderate);
        }

        if (!$can_moderate && !$event_shift->isGoing($trooper))
        {
            return $this->shiftContainerResponse($event_shift, $trooper, $can_moderate);
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
                $event_shift->event_guests()->firstOrCreate([
                    EventGuest::ADDED_BY_TROOPER_ID => $trooper->id,
                    EventGuest::NAME => $guest_name,
                    EventGuest::STATUS => $guestStatus,
                ]);
            }
        }

        $response = $this->shiftContainerResponse($event_shift, $trooper, $can_moderate);

        return $response->header('HX-Trigger', 'event-shift-guest-added');
    }

    private function shiftContainerResponse(EventShift $event_shift, $trooper, bool $can_moderate): Response
    {
        $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $trooper);
        $event_shift = $this->bus->send($event_shift_query);
        $event = $event_shift->event;
        $count_of_shifts = $event->event_shifts()->count();

        $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');
        $data['open'] = true;

        return response()->view('pages.events.inc.shift-container', $data);
    }
}
