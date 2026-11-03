<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Http\Controllers\MagicBusController;
use App\Messages\Plugins\PageData\ListPluginsPageData;
use Illuminate\Contracts\View\View;

/**
 * Shows every discovered plugin and whether it is enabled.
 */
class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(): View
    {
        return view('pages.admin.plugins.list', [
            'plugins' => ListPluginsPageData::call(),
        ]);
    }
}
