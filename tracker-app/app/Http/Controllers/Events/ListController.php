<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the list of upcoming events available for sign-up.
 *
 * This controller renders the main events listing page, showing all upcoming
 * events with their organizations, shifts, and attendance information. Only
 * events where the user's organization can attend are included.
 */
class ListController extends MagicBusController
{
    /**
     * Handle the incoming request to display the events list page
     *
     * Retrieves all upcoming events with their associated organizations
     * (filtered to only those that can attend), shifts, and organizer details.
     * Events are ordered by date and filtered to only show future events.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered events list page with upcoming events
     */
    public function __invoke(Request $request): View
    {
        $events_query = new GetEventsForDisplayQuery;

        $events = $this->bus->send($events_query);

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery);

        $data = compact('events', 'costume_organizations');

        return view('pages.events.list', $data);
    }
}
