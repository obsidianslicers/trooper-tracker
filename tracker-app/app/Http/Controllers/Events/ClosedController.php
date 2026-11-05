<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Messages\Organizations\Queries\GetOrganizations;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Reports\Queries\GetEventSummary;
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

        $events = GetEventSummary::call(
            moderator: $trooper,
            lookback: null,
            show_all: true,
            page_size: 25,
        );

        $costume_organizations = GetOrganizations::call();

        $hosting_organizations = GetOrganizationsForPicker::call(trooper: $trooper, data: []);

        $data = compact('events', 'costume_organizations', 'hosting_organizations');

        return view('pages.events.closed', $data);
    }
}
