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
 * Installs a plugin from an uploaded zip (left disabled), or upgrades an uploaded one.
 */
class InstallSubmitController extends Controller
{
    public function __invoke(InstallRequest $request): RedirectResponse
    {
        try
        {
            $result = InstallPlugin::call(
                actor: $request->user(),
                archive_path: (string) $request->file('archive')->getRealPath(),
            );
        }
        catch (PluginInstallException $e)
        {
            return back()->withErrors(['archive' => $e->getMessage()]);
        }

        $manifest = $result['manifest'];

        FlashType::success($result['previous_version'] === null
            ? "Plugin \"{$manifest->name}\" {$manifest->version} was installed. "
                .'Enable it to turn it on.'
            : "Plugin \"{$manifest->name}\" was upgraded from {$result['previous_version']} "
                ."to {$manifest->version}. Its settings and data were kept.");

        return redirect()->route('admin.plugins.list');
    }
}
