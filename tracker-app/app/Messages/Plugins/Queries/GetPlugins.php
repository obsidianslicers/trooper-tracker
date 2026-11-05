<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Queries;

use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use Hyperdrive\Message;

/**
 * Every discovered plugin with where it came from and its current state.
 *
 * @method static array call()
 */
final class GetPlugins extends Message
{
    /**
     * @return array<int, array{
     *     manifest: PluginManifest,
     *     source: string,
     *     is_enabled: bool,
     *     is_installed: bool,
     *     is_loaded: bool,
     *     installed_version: ?string,
     *     has_pending_upgrade: bool
     * }>
     */
    public function handle(PluginManager $plugins, PluginDiscovery $discovery): array
    {
        return array_values(array_map(fn (PluginManifest $manifest): array => [
            'manifest' => $manifest,
            'source' => $this->source($manifest, $discovery),
            'is_enabled' => $plugins->isEnabled($manifest->slug),
            'is_installed' => $plugins->isInstalled($manifest->slug),
            'is_loaded' => $plugins->isLoaded($manifest->slug),
            'installed_version' => $plugins->record($manifest->slug)?->installed_version,
            'has_pending_upgrade' => $plugins->hasPendingUpgrade($manifest->slug),
        ], $plugins->all()));
    }

    private function source(PluginManifest $manifest, PluginDiscovery $discovery): string
    {
        return match (true)
        {
            $manifest->is_composer_package => 'composer',
            $discovery->isUploaded($manifest) => 'uploaded',
            default => 'local',
        };
    }
}
