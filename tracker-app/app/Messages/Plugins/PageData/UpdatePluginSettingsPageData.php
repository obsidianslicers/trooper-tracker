<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

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
            'plugin' => ['slug' => $manifest->slug, 'name' => $manifest->name],
            'settings' => array_map(fn (PluginSetting $setting): array => [
                'key' => $setting->key,
                'type' => $setting->type,
                'label' => $manifest->translate($setting->label),
                'help' => $setting->help !== '' ? $manifest->translate($setting->help) : null,
                'value' => $this->formValue($setting, $values[$setting->key] ?? null),
                'is_saved' => $setting->type === 'secret' && filled($values[$setting->key] ?? null),
            ], $manifest->settings),
        ];
    }

    private function formValue(PluginSetting $setting, mixed $value): string
    {
        return match ($setting->type)
        {
            'secret' => '',
            'boolean' => $value ? '1' : '0',
            default => (string) ($value ?? ''),
        };
    }
}
