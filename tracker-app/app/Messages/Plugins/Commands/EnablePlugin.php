<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Models\Plugin;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Turns a plugin on for the whole site, running its migrations first. A bundled
 * plugin that only lacks its Composer packages is instead marked to be turned on
 * by the next deploy, which installs them (is_enable_pending on the result).
 *
 * @method static Plugin call(string $slug)
 */
final class EnablePlugin extends Message
{
    public function __construct(private readonly string $slug) {}

    public function handle(PluginManager $plugins, PluginDeployPackages $packages): Plugin
    {
        if ($packages->canInstallOnDeploy($this->slug))
        {
            return $plugins->markEnablePending($this->slug);
        }

        return $plugins->enable($this->slug);
    }
}
