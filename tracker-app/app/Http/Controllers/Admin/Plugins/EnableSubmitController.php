<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\MagicBusController;
use App\Messages\Plugins\Commands\EnablePlugin;
use App\Plugins\PluginManager;
use Illuminate\Http\RedirectResponse;

class EnableSubmitController extends MagicBusController
{
    public function __invoke(PluginManager $plugins, string $slug): RedirectResponse
    {
        $manifest = $plugins->find($slug) ?? abort(404);

        EnablePlugin::call(slug: $slug);

        FlashType::success("Plugin \"{$manifest->name}\" was enabled.");

        return redirect()->route('admin.plugins.list');
    }
}
