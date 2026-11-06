<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\PageController;
use App\Messages\Reports\Queries\GetDashboardMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying the reports menu.
 *
 * Shows the main reports landing page with links to various
 * administrative reports.
 */
class ReportDisplayController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Display the reports menu page
     *
     * @param  Request  $request  The HTTP request
     * @return View The reports menu view
     */
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', '30');

        $dashboard = GetDashboardMetrics::call(lookback: $days);

        $data = compact('dashboard', 'days');

        return view('pages.admin.reports.display', $data);
    }
}
