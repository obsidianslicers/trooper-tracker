<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Enums\PluginChangeAction;
use App\Enums\PluginSource;
use App\Models\PluginChange;
use App\Models\Trooper;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginInstaller;
use App\Plugins\PluginInstallException;
use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Log;

/**
 * Installs a plugin from an uploaded zip, or upgrades an uploaded plugin to a
 * newer version. A new plugin stays disabled until an administrator enables it.
 * An upgrade keeps the plugin's settings and data; if the plugin is enabled its
 * new migrations run, and if they fail the previous files are put back.
 *
 * An update for an enabled plugin that needs Composer packages the site doesn't
 * have waits for the next deploy, which installs them and then applies it.
 *
 * @method static array{manifest: PluginManifest, previous_version: ?string, is_staged: bool} call(
 *     Actor $actor,
 *     string $archive_path,
 * )
 */
final class InstallPlugin extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly string $archive_path,
    ) {}

    /**
     * @return array{manifest: PluginManifest, previous_version: ?string, is_staged: bool}
     *
     * @throws PluginInstallException
     */
    public function handle(
        PluginInstaller $installer,
        PluginManager $plugins,
        PluginDeployPackages $packages,
    ): array {
        $result = $installer->install($this->archive_path);
        $manifest = $result['manifest'];
        $previous_version = $result['previous_version'];
        $plugins->recordSource($manifest->slug, PluginSource::UPLOADED);

        if ($result['is_staged'])
        {
            PluginChange::record(
                $manifest->slug,
                PluginChangeAction::UPGRADE_QUEUED,
                $previous_version,
                $manifest->version,
                message: 'Waiting for the next deploy to install its Composer packages.',
            );
            $packages->writeRequirements();
        }
        elseif ($previous_version === null)
        {
            PluginChange::record(
                $manifest->slug,
                PluginChangeAction::INSTALLED,
                to_version: $manifest->version,
            );
        }
        else
        {
            $installer->applyUpgrade($manifest, $previous_version);
        }

        Log::notice("Plugin [{$manifest->slug}] {$manifest->version} was uploaded.", [
            'trooper_id' => $this->actor->id,
            'path' => $manifest->path,
            'previous_version' => $previous_version,
        ]);

        return $result;
    }
}
