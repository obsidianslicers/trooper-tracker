<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Http\Controllers\PageController;
use App\Messages\Plugins\PageData\UpdatePluginSettingsPageData;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the settings form a plugin declares in its plugin.json.
 */
class UpdateSettingsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Plugins'), 'admin.plugins.list');
    }

    public function __invoke(string $slug): Response
    {
        return Inertia::render(
            'admin/plugins/UpdateSettings',
            UpdatePluginSettingsPageData::call(slug: $slug),
        );
    }
}
