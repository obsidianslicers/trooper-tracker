<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plugins\Commands\InstallPluginRequest;
use App\Messages\Plugins\Commands\InstallPlugin;
use App\Plugins\PluginInstallException;
use Illuminate\Http\RedirectResponse;

/**
 * Installs a plugin from an uploaded zip (left disabled), or upgrades an uploaded one.
 */
class InstallPluginController extends Controller
{
    public function __invoke(InstallPluginRequest $request): RedirectResponse
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

        if ($result['is_staged'])
        {
            FlashType::info(__(
                'admin.Version :version of ":plugin" needs Composer packages. The next deploy installs them and then applies the update; :previous keeps running until then.',
                ['version' => $manifest->version, 'plugin' => $manifest->name, 'previous' => $result['previous_version']]
            ));

            return redirect()->route('admin.plugins.index');
        }

        $replace = [
            'plugin' => $manifest->name,
            'version' => $manifest->version,
            'previous' => $result['previous_version'] ?? '',
        ];

        FlashType::success($result['previous_version'] === null
            ? __('admin.Plugin ":plugin" :version was installed. Enable it to turn it on.', $replace)
            : __('admin.Plugin ":plugin" was upgraded from :previous to :version. Its settings and data were kept.', $replace));

        return redirect()->route('admin.plugins.index');
    }
}
