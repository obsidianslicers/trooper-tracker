<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports\Pages;

use App\Messages\Reports\PageData\TroopersWithoutActivityPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the Troopers Without Activity report.
 */
class ShowTroopersWithoutActivityController extends BaseReportsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/reports/TroopersWithoutActivity', TroopersWithoutActivityPageData::call($request));
    }
}
