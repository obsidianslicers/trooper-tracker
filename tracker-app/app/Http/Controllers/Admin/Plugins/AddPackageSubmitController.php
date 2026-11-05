<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plugins\AddPackageRequest;
use App\Messages\Plugins\Commands\AddPluginPackage;
use Illuminate\Http\RedirectResponse;

class AddPackageSubmitController extends Controller
{
    public function __invoke(AddPackageRequest $request): RedirectResponse
    {
        $package = AddPluginPackage::call(
            name: $request->validated('name'),
            version_constraint: $request->validated('version_constraint'),
        );

        FlashType::success("{$package->name} will be installed by the next deploy. "
            .'Then enable it here.');

        return redirect()->route('admin.plugins.list');
    }
}
