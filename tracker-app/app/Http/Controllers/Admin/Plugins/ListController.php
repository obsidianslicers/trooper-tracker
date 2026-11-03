<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Http\Controllers\Controller;
use App\Messages\Plugins\PageData\ListPluginsPageData;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;

/**
 * Shows every discovered plugin and whether it is enabled.
 */
class ListController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(): View
    {
        return view('pages.admin.plugins.list', ListPluginsPageData::call());
    }
}
