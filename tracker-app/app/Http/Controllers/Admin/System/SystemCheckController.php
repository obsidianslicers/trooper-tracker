<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\SystemCheckService;
use Illuminate\Contracts\View\View;

class SystemCheckController extends Controller
{
    public function __invoke(SystemCheckService $service): View
    {
        return view('pages.admin.system-check', [
            'checks' => $service->run(),
        ]);
    }
}
