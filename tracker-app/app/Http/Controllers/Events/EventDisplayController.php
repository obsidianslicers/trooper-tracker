<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event sign-up page with all shifts and current trooper assignments.
 *
 * This controller renders the event sign-up interface where troopers can view
 * available shifts, see who is already signed up, and register for shifts.
 * It loads the event with all related data including organizations, shifts,
 * and trooper assignments.
 */
class EventDisplayController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }

    /**
     * Handle the incoming request to display the event sign-up page.
     *
     * Loads the event with all shifts, trooper assignments, and organization
     * details. Each shift is enriched with its parent event reference for
     * easy access in the view.
     *
     * @param  Request  $request  The incoming request
     * @param  Event  $event  The event to display for sign-up
     * @return View The rendered event sign-up page
     */
    public function __invoke(Request $request, Event $event): View
    {
        $trooper = $request->user();

        $event_query = new GetEventDisplayQuery($event, $trooper);

        $event = $this->bus->send($event_query);

        $can_moderate = $trooper->isModeratorForOrganization($event->organization);

        $data = compact('event', 'can_moderate');

        return view('pages.events.event-display', $data);
    }
}
