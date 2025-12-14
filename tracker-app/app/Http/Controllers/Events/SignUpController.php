<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
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
class SignUpController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }
    /**
     * Handle the incoming request to display the account page.
     *
     * This method retrieves the authenticated user's trooper profile and
     * renders the main account management view with the trooper's data.
     *
     * @return View The rendered account page view.
     */
    public function __invoke(Request $request, Event $event): View
    {
        $with = [
            'organization',
            'organizations.organization',
            'event_shifts.event_troopers.trooper',
            'event_shifts.event_troopers.added_by_trooper',
            'event_shifts.event_troopers.organization_costume.organization',
        ];

        $event = Event::with('organization')
            ->withShifts()
            ->findOrFail($event->id);

        foreach ($event->event_shifts as $shift)
        {
            $shift->event = $event;
        }

        $data = compact('event');

        return view('pages.events.sign-up', $data);
    }
}
