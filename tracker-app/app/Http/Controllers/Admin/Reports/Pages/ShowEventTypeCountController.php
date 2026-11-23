<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports\Pages;

use App\Messages\Reports\PageData\EventTypeCountPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the Event Type Counts report.
 */
class ShowEventTypeCountController extends BaseReportsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/reports/EventTypeCount', EventTypeCountPageData::call($request));
    }
}
