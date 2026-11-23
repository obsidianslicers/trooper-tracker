<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Plugins\Commands\DisablePlugin;
use App\Plugins\PluginException;
use App\Plugins\PluginManager;
use Illuminate\Http\RedirectResponse;

class DisablePluginController extends Controller
{
    public function __invoke(PluginManager $plugins, string $slug): RedirectResponse
    {
        $manifest = $plugins->find($slug) ?? abort(404);

        try
        {
            DisablePlugin::call(slug: $slug);
        }
        catch (PluginException $e)
        {
            FlashType::danger($e->getMessage());

            return redirect()->route('admin.plugins.index');
        }

        FlashType::success(__('admin.Plugin ":plugin" was disabled.', ['plugin' => $manifest->name]));

        return redirect()->route('admin.plugins.index');
    }
}
