<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Plugins\PluginDeployPackages;
use Illuminate\Console\Command;

/**
 * Prints, one per line, the Composer packages the deploy should require for the
 * bundled plugins that are on or waiting to be turned on. deploy.sh reads it.
 */
class PluginPackagesCommand extends Command
{
    protected $signature = 'plugins:packages';

    protected $description = 'List the Composer packages enabled plugins need, for the deploy.';

    public function handle(PluginDeployPackages $packages): int
    {
        foreach ($packages->toInstall() as $name)
        {
            $this->line($name);
        }

        return self::SUCCESS;
    }
}
