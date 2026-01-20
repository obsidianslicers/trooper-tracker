<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Mail\Events\TrooperSignUp;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
     * Handle the incoming HTMX request to sign up a trooper for an event shift.
     *
     * Creates a new EventTrooper record for the authenticated user, automatically
     * determining their status (GOING or STAND_BY) based on the shift's capacity
     * limits for troopers and handlers. Returns the updated shift container view.
     *
     * @param Request $request The incoming request containing the authenticated user
     * @param EventShift $event_shift The event shift the trooper is signing up for
     * @return Response The rendered shift container with updated trooper list
     */
    public function __invoke(Request $request, EventShift $event_shift): Response
    {
        $trooper = $request->user();

        $auth_trooper = $request->user();

        if ($request->has('trooper_id'))
        {
            $trooper = Trooper::active()->findOrFail($request->input('trooper_id'));
        }

        $can_signup = false;

        if ($event_shift->canSignUp($trooper))
        {
            $event_trooper_cmd = new SignUpEventTrooperCommand($event_shift, $trooper, $auth_trooper);

            $event_trooper = $this->bus->send($event_trooper_cmd);

            $can_signup = true;

            Mail::to($trooper->email)->queue(new TrooperSignUp($event_trooper));
        }

        $event_shift_query = new GetEventShiftDisplayQuery($event_shift, $trooper);

        $event_shift = $this->bus->send($event_shift_query);

        $event = $event_shift->event;

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        $data = compact('event', 'event_shift', 'can_moderate');

        $response = response()->view('pages.events.inc.shift-container', $data);

        if (!$can_signup)
        {
            $count = $event_shift->event->getShiftCountFor($trooper);

            if ($count >= $event_shift->event->shifts_allowed)
            {
                //  handle the case where the trooper has maxed out their allowed shifts

                $message = json_encode([
                    'message' => "You have reached the maximum number of shift sign-ups allowed.",
                    'type' => 'danger',
                    'focus' => true,
                    'fadeOut' => 5000
                ]);

                $response = $response->header('X-Flash-Message', $message);
            }
        }

        return $response;
    }
}
