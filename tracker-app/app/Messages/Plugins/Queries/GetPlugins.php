<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Queries;

use App\Enums\PluginCapability;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use Hyperdrive\Message;
use Illuminate\Support\Arr;

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
     *     has_pending_upgrade: bool,
     *     unmet_requirements: array<int, string>,
     *     capability_summary: string,
     *     is_enable_pending: bool,
     *     can_install_on_deploy: bool
     * }>
     */
    public function handle(
        PluginManager $plugins,
        PluginDiscovery $discovery,
        PluginDeployPackages $packages,
    ): array {
        return array_values(array_map(fn (PluginManifest $manifest): array => [
            'manifest' => $manifest,
            'source' => $this->source($manifest, $discovery),
            'is_enabled' => $plugins->isEnabled($manifest->slug),
            'is_installed' => $plugins->isInstalled($manifest->slug),
            'is_loaded' => $plugins->isLoaded($manifest->slug),
            'installed_version' => $plugins->record($manifest->slug)?->installed_version,
            'has_pending_upgrade' => $plugins->hasPendingUpgrade($manifest->slug),
            'unmet_requirements' => $plugins->unmetRequirements($manifest->slug),
            'capability_summary' => $this->capabilitySummary($manifest),
            'is_enable_pending' => $plugins->isEnablePending($manifest->slug),
            'can_install_on_deploy' => $packages->canInstallOnDeploy($manifest->slug),
        ], $plugins->all()));
    }

    /**
     * e.g. "reacts when things happen, such as events being posted, and adds a way to sign in"
     */
    private function capabilitySummary(PluginManifest $manifest): string
    {
        $descriptions = array_map(
            fn (PluginCapability $capability): string => $capability->description(),
            $manifest->capabilities,
        );

        return Arr::join($descriptions, ', ', count($descriptions) > 2 ? ', and ' : ' and ');
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
