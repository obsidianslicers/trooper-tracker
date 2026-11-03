<?php

declare(strict_types=1);

namespace App\Messages\Plugins\PageData;

use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use Hyperdrive\Message;

/**
 * Every discovered plugin with its enabled state, for the Admin > Plugins page.
 *
 * @method static array call()
 */
final class ListPluginsPageData extends Message
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(PluginManager $plugins): array
    {
        return array_values(array_map(fn (PluginManifest $manifest): array => [
            'manifest' => $manifest,
            'is_enabled' => $plugins->isEnabled($manifest->slug),
            'is_loaded' => $plugins->isLoaded($manifest->slug),
            'settings' => $plugins->settings($manifest->slug),
            'enabled_at' => $plugins->record($manifest->slug)?->enabled_at,
        ], $plugins->all()));
    }
}
