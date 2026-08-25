<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\LogViewerService;
use App\Services\SystemCheckService;
use Illuminate\Contracts\View\View;

class SystemCheckController extends Controller
{
    public function __invoke(SystemCheckService $service, LogViewerService $log_viewer): View
    {
        return view('pages.admin.system-check', [
            'checks' => $service->run(),
            'recent_errors' => $log_viewer->recentErrors(),
        ]);
    }
}
