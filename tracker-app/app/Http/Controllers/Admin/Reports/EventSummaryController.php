<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetEventSummaryQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying event summary report.
 *
 * Shows closed events within the lookback period with shift counts
 * and trooper participation metrics.
 */
class EventSummaryController extends BaseReportsController
{
    /**
     * Display the event summary report.
     *
     * Retrieves closed events moderated by the authenticated trooper
     * and displays statistics including shift counts, total troopers,
     * and unique troopers per event.
     *
     * @param  Request  $request  The HTTP request.
     * @return View The event summary report view.
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
