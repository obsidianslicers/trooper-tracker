<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Plugins\PluginDeployPackages;
use Illuminate\Console\Command;

/**
 * Turns on the plugins an administrator enabled before their packages were
 * installed. deploy.sh runs it after installing them and migrating.
 */
class EnablePendingPluginsCommand extends Command
{
    protected $signature = 'plugins:enable-pending';

    protected $description = 'Turn on plugins that were waiting for their packages.';

    public function handle(PluginDeployPackages $packages): int
    {
        $results = $packages->enablePending();
        $failed = false;

        foreach ($results as $slug => $error)
        {
            if ($error === null)
            {
                $this->info("Plugin [{$slug}] enabled.");

                continue;
            }

            $failed = true;
            $this->error("Plugin [{$slug}] is still waiting: {$error}");
        }

        if ($results === [])
        {
            $this->info('No plugins are waiting to be turned on.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
