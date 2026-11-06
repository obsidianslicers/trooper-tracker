<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Messages\Reports\Queries\GetEventSummary;
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
     * Display the event summary report
     *
     * Retrieves closed events moderated by the authenticated trooper
     * and displays statistics including shift counts, total troopers,
     * and unique troopers per event.
     *
     * @param  Request  $request  The HTTP request
     * @return View The event summary report view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $events = GetEventSummary::call(moderator: $trooper, lookback: $lookback);

        $data = compact('events', 'lookback');

        return view('pages.admin.reports.event-summary', $data);
    }
}
