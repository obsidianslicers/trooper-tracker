<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Plugins\Commands\UninstallPlugin;
use App\Plugins\PluginManager;
use Illuminate\Http\RedirectResponse;

class UninstallSubmitController extends Controller
{
    public function __invoke(PluginManager $plugins, string $slug): RedirectResponse
    {
        $manifest = $plugins->find($slug) ?? abort(404);

        UninstallPlugin::call(slug: $slug);

        FlashType::success("Plugin \"{$manifest->name}\" was uninstalled and its data removed.");

        return redirect()->route('admin.plugins.list');
    }
}
