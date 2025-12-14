<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\SetupUpdateHtmxRequest;
use App\Models\EventTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Handles the display of the main account management page.
 *
 * This controller is responsible for fetching the authenticated user's
 * data and rendering the primary account view where they can manage
 * their profile, settings, and other account-related information.
 */
class SignUpUpdateHtmxController extends Controller
{
    /**
     * Handle the incoming request to display the account page.
     *
     * This method retrieves the authenticated user's trooper profile and
     * renders the main account management view with the trooper's data.
     *
     * @return View The rendered account page view.
     */
    public function __invoke(SetupUpdateHtmxRequest $request, EventTrooper $event_trooper): Response
    {
        $request->validateInputs();

        if ($request->has('status'))
        {
            $is_full = false;

            if ($event_trooper->is_handler)
            {
                $is_full = $event_trooper->event_shift->handlersMaxed();
            }
            else
            {
                $is_full = $event_trooper->event_shift->troopersMaxed();
            }

            $event_trooper->status = $request->validated('status');
            $event_trooper->save();

            if ($is_full && $event_trooper->status == EventTrooperStatus::CANCELLED)
            {
                // notify next in line that they can now attend
                $next_in_line = $event_trooper->event_shift
                    ->event_troopers()
                    ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
                    ->orderBy(EventTrooper::SIGNED_UP_AT)
                    ->first();

                $next_in_line->status = EventTrooperStatus::GOING;
                $next_in_line->save();
            }
        }
        elseif ($request->has('costume_id'))
        {
            $event_trooper->costume_id = $request->validated('costume_id');
            $event_trooper->save();
        }

        return response('ok', 200);
    }
}
