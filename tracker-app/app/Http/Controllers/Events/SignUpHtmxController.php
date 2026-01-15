<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Controllers\Controller;
use App\Mail\Events\TrooperSignUp;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
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
class SignUpHtmxController extends Controller
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
            $event_trooper = $this->addTrooper($event_shift, $trooper);

            $can_signup = true;

            Mail::to($trooper->email)->queue(new TrooperSignUp($event_trooper));
        }

        $with = [
            'event_troopers.trooper',
            'event_troopers.added_by_trooper',
            'event_troopers.organization_costume.organization',
            'event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
        ];

        $event_shift = EventShift::with($with)->findOrFail($event_shift->id);

        $event = $event_shift->event;

        //  re-link shifts to event for view access (see SignUpController)
        $event_shift->event = $event;

        foreach ($event_shift->event_troopers as $event_trooper)
        {
            $event_trooper->event_shift = $event_shift;

            if ($event_trooper->canUpdateCostume($event_shift, $auth_trooper))
            {
                //  performance optimization: load costumes only if the trooper can update
                $event_trooper->costumes = $event_trooper->getCostumes();
            }
        }

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

    private function addTrooper(EventShift $event_shift, Trooper $trooper): EventTrooper
    {
        $current_id = Auth::user()->id;

        $event_trooper = new EventTrooper();

        $event_trooper->event_shift_id = $event_shift->id;
        $event_trooper->trooper_id = $trooper->id;
        $event_trooper->is_handler = $trooper->is_handler;
        $event_trooper->signed_up_at = now();
        $event_trooper->added_by_trooper_id = $current_id == $trooper->id ? null : $current_id;

        $status = EventTrooperStatus::GOING;

        if ($event_trooper->is_handler)
        {
            if ($event_shift->handlersMaxed())
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }
        else
        {
            if ($event_shift->troopersMaxed())
            {
                $status = EventTrooperStatus::STAND_BY;
            }
        }

        $event_trooper->status = $status;
        $event_trooper->save();

        return $event_trooper;
    }
}
