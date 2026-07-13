<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Organizations\Queries\GetOrganizationsForPickerQuery;
use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Features\Reports\Queries\GetEventSummaryQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the list of closed events.
 *
 * This controller renders the closed events listing page, showing events
 * from the complete event history with their organizations, shifts,
 * and attendance information.
 */
class ClosedController extends MagicBusController
{
    /**
     * Handle the incoming request to display the closed events list page
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered closed events list page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $events_query = new GetEventSummaryQuery(
            moderator: $trooper,
            lookback: null,
            show_all: true,
            page_size: 25,
        );

        $events = $this->bus->send($events_query);

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery);

        $hosting_organizations = $this->bus->send(new GetOrganizationsForPickerQuery($trooper, []));

        $data = compact('events', 'costume_organizations', 'hosting_organizations');

        return view('pages.events.closed', $data);
    }
}
