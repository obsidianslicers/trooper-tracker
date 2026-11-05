<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Enums\PluginChangeAction;
use App\Enums\PluginSource;
use App\Models\PluginChange;
use App\Models\Trooper;
use App\Plugins\PluginException;
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
 * @method static array{manifest: PluginManifest, previous_version: ?string} call(
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
     * @return array{manifest: PluginManifest, previous_version: ?string}
     *
     * @throws PluginInstallException
     */
    public function handle(PluginInstaller $installer, PluginManager $plugins): array
    {
        $result = $installer->install($this->archive_path);
        $manifest = $result['manifest'];
        $previous_version = $result['previous_version'];
        $plugins->recordSource($manifest->slug, PluginSource::UPLOADED);

        if ($previous_version === null)
        {
            PluginChange::record(
                $manifest->slug,
                PluginChangeAction::INSTALLED,
                to_version: $manifest->version,
            );
        }
        else
        {
            $this->upgrade($installer, $plugins, $manifest, $previous_version);
        }

        Log::notice("Plugin [{$manifest->slug}] {$manifest->version} was uploaded.", [
            'trooper_id' => $this->actor->id,
            'path' => $manifest->path,
            'previous_version' => $previous_version,
        ]);

        return $result;
    }

    private function upgrade(
        PluginInstaller $installer,
        PluginManager $plugins,
        PluginManifest $manifest,
        string $previous_version,
    ): void {
        if (!$plugins->isEnabled($manifest->slug))
        {
            $this->recordFilesReplaced($manifest, $previous_version);
            $installer->forgetPrevious($manifest);

            return;
        }

        try
        {
            $plugins->upgrade($manifest->slug);
        }
        catch (PluginException $e)
        {
            $installer->restorePrevious($manifest);

            throw new PluginInstallException(
                "The upgrade to {$manifest->version} failed, so version {$previous_version} "
                .'was put back. '.$e->getMessage(),
                previous: $e,
            );
        }

        $installer->forgetPrevious($manifest);
    }

    private function recordFilesReplaced(PluginManifest $manifest, string $previous_version): void
    {
        PluginChange::record(
            $manifest->slug,
            PluginChangeAction::UPGRADED,
            $previous_version,
            $manifest->version,
            message: 'Files replaced; migrations run when it is enabled.',
        );
    }
}
