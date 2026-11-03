<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plugins\InstallRequest;
use App\Messages\Plugins\Commands\InstallPlugin;
use App\Plugins\PluginInstallException;
use Illuminate\Http\RedirectResponse;

/**
 * Installs a plugin from an uploaded zip. The plugin is left disabled.
 */
class InstallSubmitController extends Controller
{
    public function __invoke(InstallRequest $request): RedirectResponse
    {
        try
        {
            $manifest = InstallPlugin::call(
                actor: $request->user(),
                archive_path: (string) $request->file('archive')->getRealPath(),
            );
        }
        catch (PluginInstallException $e)
        {
            return back()->withErrors(['archive' => $e->getMessage()]);
        }

        FlashType::success(
            "Plugin \"{$manifest->name}\" {$manifest->version} was installed. "
            .'Enable it to turn it on.'
        );

        return redirect()->route('admin.plugins.list');
    }
}
