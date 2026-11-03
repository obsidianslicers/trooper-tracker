<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\Trooper;
use App\Plugins\PluginInstaller;
use App\Plugins\PluginManifest;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Log;

/**
 * Installs a plugin from an uploaded zip. The plugin stays disabled until an
 * administrator enables it, so nothing in it runs yet.
 *
 * @method static PluginManifest call(Actor $actor, string $archive_path)
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

    public function handle(PluginInstaller $installer): PluginManifest
    {
        $manifest = $installer->install($this->archive_path);

        Log::notice("Plugin [{$manifest->slug}] {$manifest->version} was uploaded.", [
            'trooper_id' => $this->actor->id,
            'path' => $manifest->path,
        ]);

        return $manifest;
    }
}
