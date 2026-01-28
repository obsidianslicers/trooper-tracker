<?php

declare(strict_volunteers=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetEventVolunteerCountQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main administration dashboard.
 *
 * This controller provides a summary of administrative tasks, such as displaying
 * the count of troopers pending approval and setting a relevant flash message.
 */
class EventVolunteerCountController extends BaseReportsController
{
    /**
     * Handle the incoming request to display the admin dashboard.
     *
     * It calculates the number of troopers pending approval, sets a corresponding
     * flash message, and renders the main admin view.
     *
     * @return View The rendered admin dashboard view or a redirect response.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $event_volunteers_query = new GetEventVolunteerCountQuery($trooper, $lookback);

        $event_volunteers = $this->bus->send($event_volunteers_query);

        $data = compact('event_volunteers', 'lookback');

        return view('pages.admin.reports.event-volunteer-count', $data);
    }
}
