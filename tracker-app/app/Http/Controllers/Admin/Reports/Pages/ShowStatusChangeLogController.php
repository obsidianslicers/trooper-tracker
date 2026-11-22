<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports\Pages;

use App\Messages\Reports\PageData\StatusChangeLogPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the Status Change Log report.
 */
class ShowStatusChangeLogController extends BaseReportsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/reports/StatusChangeLog', StatusChangeLogPageData::call($request));
    }
}
