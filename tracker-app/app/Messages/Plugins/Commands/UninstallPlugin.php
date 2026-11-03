<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use Hyperdrive\Message;
use Illuminate\Filesystem\Filesystem;

/**
 * Turns a plugin off and deletes its data: rolls back its migrations and removes
 * its settings. A plugin that was uploaded from the app also has its files
 * deleted. Other plugins keep their files until a deploy removes them.
 *
 * @method static void call(string $slug)
 */
final class UninstallPlugin extends Message
{
    public function __construct(private readonly string $slug) {}

    public function handle(
        PluginManager $plugins,
        PluginDiscovery $discovery,
        Filesystem $files,
    ): void {
        $manifest = $plugins->findOrFail($this->slug);

        $plugins->uninstall($this->slug);

        if ($discovery->isUploaded($manifest))
        {
            $files->deleteDirectory($manifest->path);
            $plugins->forgetManifests();
        }
    }
}
