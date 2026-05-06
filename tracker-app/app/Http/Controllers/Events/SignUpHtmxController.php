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

        if ($event_shift->canSignUp($trooper))
        {
            $organization_id = $request->input('organization_id') ? (int) $request->input('organization_id') : null;

            $is_handler = (bool) $request->input('is_handler', $trooper->is_handler ? 1 : 0);

            $event_trooper_cmd = new SignUpEventTrooperCommand($event_shift, $trooper, $auth_trooper, $organization_id, $is_handler);

            $this->bus->send($event_trooper_cmd);

            $signed_up = true;
        }

        $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $trooper);

        $event_shift = $this->bus->send($event_shift_query);

        $event = $event_shift->event;

        $can_moderate = $auth_trooper->isModeratorForOrganization($event->organization);

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
