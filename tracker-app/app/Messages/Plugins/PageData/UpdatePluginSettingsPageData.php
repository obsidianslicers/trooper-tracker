<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Messages\Plugins\Resources\PluginSettingFieldResource;
use App\Messages\Plugins\Resources\PluginSettingsPluginResource;
use App\Plugins\PluginManager;
use App\Plugins\PluginSetting;
use Hyperdrive\Message;

/**
 * The settings form a plugin declares in its plugin.json. Saved secrets never leave the server:
 * the form only learns that one is set.
 *
 * @method static array call(string $slug)
 */
final class UpdatePluginSettingsPageData extends Message
{
    public function __construct(private readonly string $slug) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(PluginManager $plugins): array
    {
        $manifest = $plugins->find($this->slug) ?? abort(404);
        $values = $plugins->settings($this->slug);

        return [
            'plugin' => new PluginSettingsPluginResource($manifest),
            'settings' => array_map(
                fn (PluginSetting $setting) => new PluginSettingFieldResource(
                    $setting,
                    $manifest,
                    $values[$setting->key] ?? null,
                ),
                $manifest->settings
            ),
        ];
    }
}
