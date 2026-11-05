<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Http\Controllers\PageController;
use App\Plugins\PluginManager;
use Illuminate\Contracts\View\View;

/**
 * Shows the settings form a plugin declares in its plugin.json.
 */
class UpdateSettingsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Plugins', 'admin.plugins.list');
    }

    public function __invoke(PluginManager $plugins, string $slug): View
    {
        $manifest = $plugins->find($slug) ?? abort(404);

        return view('pages.admin.plugins.update-settings', [
            'manifest' => $manifest,
            'settings' => $plugins->settings($slug),
        ]);
    }
}
