<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Http\Controllers\PageController;
use App\Messages\Plugins\PageData\ListPluginsPageData;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows every discovered plugin and whether it is enabled.
 */
class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
    }

    public function __invoke(): Response
    {
        return Inertia::render('admin/plugins/List', ListPluginsPageData::call());
    }
}
