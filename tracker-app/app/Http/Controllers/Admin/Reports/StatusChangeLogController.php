<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetStatusChangeLogQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying status change log report.
 *
 * Shows event trooper status changes where a moderator marked
 * a trooper as ATTENDED within the lookback period.
 */
class StatusChangeLogController extends BaseReportsController
{
    /**
     * Display the status change log report
     *
     * Retrieves EventTrooper records marked as ATTENDED by moderators
     * (not self-updated) within the lookback period for troopers
     * moderated by the authenticated trooper.
     *
     * @param  Request  $request  The HTTP request
     * @return View The status change log report view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $changes_query = new GetStatusChangeLogQuery($trooper, $lookback);

        $changes = $this->bus->send($changes_query);

        $data = compact('changes', 'lookback');

        return view('pages.admin.reports.status-change-log', $data);
    }
}
