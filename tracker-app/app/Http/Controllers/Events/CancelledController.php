<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\PageController;
use App\Messages\Organizations\Queries\GetOrganizations;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Messages\Reports\Queries\GetEventSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the list of recently cancelled events.
 *
 * This controller renders the cancelled events listing page, showing events
 * that were cancelled within the last 30 days with their organizations,
 * shifts, and attendance information.
 */
class CancelledController extends PageController
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

        $events = GetEventSummary::call(
            moderator: $trooper,
            lookback: $lookback,
            show_all: true,
            status: EventStatus::CANCELLED,
        );

        $costume_organizations = GetOrganizations::call();

        $hosting_organizations = GetOrganizationsForPicker::call(trooper: $trooper, data: []);

        $data = compact('events', 'lookback', 'costume_organizations', 'hosting_organizations');

        return view('pages.events.cancelled', $data);
    }
}
