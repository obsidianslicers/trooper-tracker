<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins;

use App\Enums\FlashType;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Plugins\UpdateSettingsRequest;
use App\Messages\Plugins\Commands\UpdatePluginSettings;
use Illuminate\Http\RedirectResponse;

class UpdateSettingsSubmitController extends MagicBusController
{
    public function __invoke(UpdateSettingsRequest $request, string $slug): RedirectResponse
    {
        $manifest = $request->manifest();

        UpdatePluginSettings::call(slug: $slug, settings: $request->validated('settings') ?? []);

        FlashType::success("Settings for \"{$manifest->name}\" were saved.");

        return redirect()->route('admin.plugins.list');
    }
}
