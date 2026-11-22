<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Messages\Reports\PageData\EventSummaryPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the Event Summary report.
 */
class EventSummaryController extends BaseReportsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/reports/EventSummary', EventSummaryPageData::call($request));
    }
}
