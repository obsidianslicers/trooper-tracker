<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles HTMX-driven event shift sign-up creation.
 *
 * This controller processes a trooper's sign-up for a specific event shift.
 * It automatically determines if the trooper should be placed on the main roster
 * or the standby list based on shift capacity, and returns the updated shift
 * container view for dynamic page updates.
 */
class SignUpHtmxController extends MagicBusController
{
    /**
     * Handle the incoming HTMX request to sign up a trooper for an event shift
     *
     * Creates a new EventTrooper record for the specified or authenticated trooper,
     * automatically determining their status (GOING or STAND_BY) based on the shift's
     * capacity limits. Supports moderators signing up other troopers via trooper_id
     * parameter. Returns the updated shift container view.
     *
     * @param  Request  $request  The incoming request (may contain optional trooper_id for moderator signups)
     * @param  EventShift  $event_shift  The event shift the trooper is signing up for
     * @return Response The rendered shift container with updated trooper list
     */
    public function __invoke(Request $request, EventShift $event_shift): Response
    {
        $trooper = $request->user();

        $auth_trooper = $request->user();

        if ($request->has('trooper_id'))
        {
            //  auth_trooper is signing up on behalf of another trooper
            $trooper = Trooper::active()->findOrFail($request->input('trooper_id'));
        }

        $signed_up = false;

        $can_moderate = $auth_trooper->isModeratorForOrganization($event_shift->event->organization);

        $can_add_friend = !$request->has('trooper_id')
            || $can_moderate
            || $event_shift->canSignUpTrooper($auth_trooper);

        $grace_period_moderator_add = $can_moderate
            && $event_shift->event->is_within_grace_period
            && !$event_shift->isSignedUp($trooper);

        $is_friend_add = $request->has('trooper_id') && $trooper->id !== $auth_trooper->id;

        if ($can_add_friend && ($event_shift->canSignUp($trooper, require_own_mission_brief_ack: !$is_friend_add) || $grace_period_moderator_add))
        {
            $event_shift->loadMissing('event_shift_stations');

            $event_shift_station_id = $request->input('event_shift_station_id')
                ? (int) $request->input('event_shift_station_id')
                : null;

            if (!$event_shift->isValidStationChoice($event_shift_station_id))
            {
                return $this->shiftContainerResponse($event_shift, $auth_trooper, $trooper, $can_moderate)
                    ->header('X-Flash-Message', json_encode([
                        'message' => 'Select a station before signing up.',
                        'type' => 'danger',
                        'focus' => true,
                        'fadeOut' => 5000,
                    ]));
            }

            $organization_id = $request->input('organization_id') ? (int) $request->input('organization_id') : null;

            $is_handler = (bool) $request->input('is_handler', $trooper->is_handler ? 1 : 0);

            $event_trooper_cmd = new SignUpEventTrooperCommand(
                $event_shift,
                $trooper,
                $auth_trooper,
                $organization_id,
                $is_handler,
                event_shift_station_id: $event_shift_station_id,
            );

            $this->bus->send($event_trooper_cmd);

            $signed_up = true;
        }

        return $this->shiftContainerResponse($event_shift, $auth_trooper, $trooper, $can_moderate, $signed_up);
    }

    private function shiftContainerResponse(EventShift $event_shift, Trooper $auth_trooper, Trooper $trooper, bool $can_moderate, bool $signed_up = true): Response
    {
        $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $auth_trooper);
        $event_shift = $this->bus->send($event_shift_query);

        $event = $event_shift->event;

        $count_of_shifts = $event->event_shifts()->count();

        $data = compact('event', 'event_shift', 'can_moderate', 'count_of_shifts');

        $data['open'] = true;

        $response = response()->view('pages.events.inc.shift-container', $data);

        if (!$signed_up)
        {
            $count = $event_shift->event->getShiftCountFor($trooper);

            if ($event_shift->event->shifts_allowed !== null && $count >= $event_shift->event->shifts_allowed)
            {
                //  handle the case where the trooper has maxed out their allowed shifts

                $message = json_encode([
                    'message' => 'You have reached the maximum number of shift sign-ups allowed.',
                    'type' => 'danger',
                    'focus' => true,
                    'fadeOut' => 5000,
                ]);

                $response = $response->header('X-Flash-Message', $message);
            }
        }

        return $response;
    }
}
