<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Controllers\Controller;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main account management page.
 *
 * This controller is responsible for fetching the authenticated user's
 * data and rendering the primary account view where they can manage
 * their profile, settings, and other account-related information.
 */
class SignUpHtmxController extends Controller
{
    /**
     * Handle the incoming request to display the account page.
     *
     * This method retrieves the authenticated user's trooper profile and
     * renders the main account management view with the trooper's data.
     *
     * @return View The rendered account page view.
     */
    public function __invoke(Request $request, EventShift $event_shift): View
    {
        $event_trooper = new EventTrooper();

        $event_trooper->event_shift_id = $event_shift->id;
        $event_trooper->trooper_id = $request->user()->id;
        $event_trooper->is_handler = $request->user()->isHandler();
        $event_trooper->signed_up_at = now();

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

        $with = [
            'event_troopers.trooper',
            'event_troopers.added_by_trooper',
            'event_troopers.organization_costume.organization',
        ];

        $event_shift = EventShift::with($with)->findOrFail($event_shift->id);

        $event = $event_shift->event;

        $data = compact('event', 'event_shift');

        return view('pages.events.inc.shift-container', $data);
    }
}
