<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\PageController;
use App\Messages\System\PageData\CommandStaffDashboardPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the Command Staff dashboard.
 */
class AdminDisplayController extends PageController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('admin/Dashboard', CommandStaffDashboardPageData::call($request));
    }
}
