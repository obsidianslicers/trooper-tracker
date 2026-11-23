<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Pages;

use App\Http\Controllers\Controller;
use App\Messages\System\PageData\SystemCheckPageData;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the System Check page.
 */
class ShowSystemCheckController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/SystemCheck', SystemCheckPageData::call());
    }
}
