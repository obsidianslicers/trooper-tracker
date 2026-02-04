<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetTroopersWithoutActivityQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying troopers without recent activity.
 *
 * Shows active troopers who have not attended events within
 * the lookback period (12 months by default).
 */
class TroopersWithoutActivityController extends BaseReportsController
{
    /**
     * Display the troopers without activity report
     *
     * Retrieves active troopers moderated by the authenticated trooper
     * who have no ATTENDED event signups within the last 12 months.
     *
     * @param  Request  $request  The HTTP request
     * @return View The troopers without activity report view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = now()->subMonths(12);

        $troopers_query = new GetTroopersWithoutActivityQuery($trooper, $lookback);

        $troopers = $this->bus->send($troopers_query);

        $data = compact('troopers', 'lookback');

        return view('pages.admin.reports.troopers-without-activity', $data);
    }
}
