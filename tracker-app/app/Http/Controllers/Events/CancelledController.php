<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Features\Reports\Queries\GetEventSummaryQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the list of recently cancelled events.
 *
 * This controller renders the cancelled events listing page, showing events
 * that were cancelled within the last 30 days with their organizations,
 * shifts, and attendance information.
 */
class CancelledController extends MagicBusController
{
    /**
     * Handle the incoming request to display the cancelled events list page
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered cancelled events list page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $events_query = new GetEventSummaryQuery($trooper, $lookback, true);

        $events = $this->bus->send($events_query);

        $costume_organizations = $this->bus->send(new GetOrganizationsQuery);

        $data = compact('events', 'lookback', 'costume_organizations');

        return view('pages.events.cancelled', $data);
    }
}
