<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetEventSummaryQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main administration dashboard.
 *
 * This controller provides a summary of administrative tasks, such as displaying
 * the count of troopers pending approval and setting a relevant flash message.
 */
class EventSummaryController extends BaseReportsController
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

        $events_query = new GetEventSummaryQuery($trooper, $lookback);

        $events = $this->bus->send($events_query);

        $data = compact('events', 'lookback');

        return view('pages.admin.reports.event-summary', $data);
    }
}
