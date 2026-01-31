<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Features\Reports\Queries\GetEventSummaryQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the list of recently finished events.
 *
 * This controller renders the main events listing page, showing all recently finished
 * events with their organizations, shifts, and attendance information. Only
 * events where the user's organization can attend are included.
 */
class ClosedController extends MagicBusController
{
    /**
     * Handle the incoming request to display the events list page.
     *
     * Retrieves all recently closed events with their associated organizations
     * (filtered to only those that can attend), shifts, and organizer details.
     * Events are ordered by date and filtered to only show future events.
     *
     * @param Request $request The incoming request
     * @return View The rendered events list page with upcoming events
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $events_query = new GetEventSummaryQuery($trooper, $lookback, true);

        $events = $this->bus->send($events_query);

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery());

        $data = compact('events', 'lookback', 'costume_organizations');

        return view('pages.events.closed', $data);
    }
}
