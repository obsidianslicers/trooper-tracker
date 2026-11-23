<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Plugins\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Plugins\Commands\UpdatePluginSettingsRequest;
use App\Messages\Plugins\Commands\UpdatePluginSettings;
use Illuminate\Http\RedirectResponse;

class UpdatePluginSettingsController extends Controller
{
    public function __invoke(UpdatePluginSettingsRequest $request, string $slug): RedirectResponse
    {
        $manifest = $request->manifest();

        UpdatePluginSettings::call(slug: $slug, settings: $request->validated('settings') ?? []);

        FlashType::success(__('admin.Settings for ":plugin" were saved.', ['plugin' => $manifest->name]));

        return redirect()->route('admin.plugins.index');
    }
}
