<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying trooper event participation summary.
 *
 * Shows statistics for each trooper's event attendance within
 * the lookback period.
 */
class TrooperEventSummaryController extends BaseReportsController
{
    /**
     * Display the trooper event summary report
     *
     * Retrieves troopers who attended events within the lookback period
     * with statistics including total shifts attended, unique events attended,
     * and event IDs.
     *
     * @param  Request  $request  The HTTP request
     * @return View The trooper event summary report view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $trooper_events_query = new GetTrooperEventSummaryQuery($trooper, $lookback);

        $trooper_events = $this->bus->send($trooper_events_query);

        $data = compact('trooper_events', 'lookback');

        return view('pages.admin.reports.trooper-event-summary', $data);
    }
}
