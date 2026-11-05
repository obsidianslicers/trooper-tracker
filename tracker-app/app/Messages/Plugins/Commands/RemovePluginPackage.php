<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\PluginPackage;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginManager;
use App\Plugins\PluginPackageException;
use Hyperdrive\Message;

/**
 * Stops the deploy installing a Composer-managed plugin, so the next deploy
 * removes it. Refused while the plugin it provides is still turned on.
 *
 * @method static void call(string $name)
 */
final class RemovePluginPackage extends Message
{
    public function __construct(private readonly string $name) {}

    /**
     * @throws PluginPackageException
     */
    public function handle(PluginManager $plugins, PluginDeployPackages $packages): void
    {
        $manifest = $packages->manifestFromPackage($this->name);

        if ($manifest !== null && $plugins->isEnabled($manifest->slug))
        {
            throw new PluginPackageException(
                "{$manifest->name} comes from {$this->name}. Turn it off first."
            );
        }

        PluginPackage::query()->where(PluginPackage::NAME, $this->name)->delete();
        $packages->writeRequirements();
    }
}
