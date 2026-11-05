<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Plugins\Commands\EnablePlugin;
use App\Plugins\PluginManager;
use App\Plugins\PluginMigrationException;
use Illuminate\Http\RedirectResponse;

class EnableSubmitController extends Controller
{
    public function __invoke(PluginManager $plugins, string $slug): RedirectResponse
    {
        $manifest = $plugins->find($slug) ?? abort(404);

        try
        {
            EnablePlugin::call(slug: $slug);
        }
        catch (PluginMigrationException $e)
        {
            FlashType::danger($e->getMessage());

            return redirect()->route('admin.plugins.list');
        }

        FlashType::success("Plugin \"{$manifest->name}\" was enabled.");

        return redirect()->route('admin.plugins.list');
    }
}
