<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Plugins\Commands\EnablePlugin;
use App\Plugins\PluginException;
use App\Plugins\PluginManager;
use Illuminate\Http\RedirectResponse;

class EnableSubmitController extends Controller
{
    public function __invoke(PluginManager $plugins, string $slug): RedirectResponse
    {
        $manifest = $plugins->find($slug) ?? abort(404);

        try
        {
            $record = EnablePlugin::call(slug: $slug);
        }
        catch (PluginException $e)
        {
            FlashType::danger($e->getMessage());

            return redirect()->route('admin.plugins.list');
        }

        if ($record->is_enable_pending)
        {
            FlashType::info("\"{$manifest->name}\" needs packages that are installed on deploy. "
                .'It turns on with the next deploy.');

            return redirect()->route('admin.plugins.list');
        }

        FlashType::success("Plugin \"{$manifest->name}\" was enabled.");

        return redirect()->route('admin.plugins.list');
    }
}
