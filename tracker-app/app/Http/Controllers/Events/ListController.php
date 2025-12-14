<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main account management page.
 *
 * This controller is responsible for fetching the authenticated user's
 * data and rendering the primary account view where they can manage
 * their profile, settings, and other account-related information.
 */
class ListController extends Controller
{
    /**
     * Handle the incoming request to display the account page.
     *
     * This method retrieves the authenticated user's trooper profile and
     * renders the main account management view with the trooper's data.
     *
     * @return View The rendered account page view.
     */
    public function __invoke(Request $request): View
    {
        $with = ['organization', 'organizations' => function ($query)
        {
            $query->wherePivot(EventOrganization::CAN_ATTEND, true);
        }];

        $events = Event::with($with)
            ->withShifts()
            ->upcoming()
            ->get();

        $data = compact('events');

        return view('pages.events.list', $data);
    }
}
