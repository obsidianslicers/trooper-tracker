<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Reports\PageData\ReportsDashboardPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the Reports page: the operations dashboard and the list of reports.
 */
class IndexReportsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
    }

    public function __invoke(Request $request): Response
    {
        $days = (int) $request->query('days', '30');

        return Inertia::render('admin/reports/Display', ReportsDashboardPageData::call(days: $days));
    }
}
