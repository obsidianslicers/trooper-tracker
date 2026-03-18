<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventGuestStatus;
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

        $guest_names = $request->input('guest_names');

        foreach (explode('\\n', $guest_names) as $guest_name)
        {
            $guest_name = trim($guest_name);

            if (!empty($guest_name))
            {
                $event_shift->event_guests()->firstOrCreate([
                    EventGuest::ADDED_BY_TROOPER_ID => $trooper->id,
                    EventGuest::NAME => $guest_name,
                    EventGuest::STATUS => EventGuestStatus::GOING,
                ]);
            }
        }

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        $count_of_shifts = $event->event_shifts()->count();

        $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');

        $data['open'] = true;

        $response = response()->view('pages.events.inc.shift-container', $data);

        $response = $response->header('HX-Trigger', 'event-shift-guest-added');

        return $response;
    }
}
