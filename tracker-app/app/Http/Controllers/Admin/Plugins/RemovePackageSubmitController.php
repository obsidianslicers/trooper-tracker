<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Plugins\Commands\RemovePluginPackage;
use App\Models\PluginPackage;
use App\Plugins\PluginException;
use Illuminate\Http\RedirectResponse;

class RemovePackageSubmitController extends Controller
{
    public function __invoke(PluginPackage $package): RedirectResponse
    {
        try
        {
            RemovePluginPackage::call(name: $package->name);
        }
        catch (PluginException $e)
        {
            FlashType::danger($e->getMessage());

            return redirect()->route('admin.plugins.list');
        }

        FlashType::success("{$package->name} will be removed by the next deploy.");

        return redirect()->route('admin.plugins.list');
    }
}
