<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\EnablePlugin;
use App\Plugins\PluginManager;
use App\Plugins\PluginMigrationException;
use Illuminate\Console\Command;

class EnablePluginCommand extends Command
{
    protected $signature = 'plugins:enable {slug : The plugin slug from plugins:list}';

    protected $description = 'Enable a plugin for the whole site and run its migrations.';

    public function handle(PluginManager $plugins): int
    {
        $slug = (string) $this->argument('slug');

        if ($plugins->find($slug) === null)
        {
            $this->error("No plugin found with slug [{$slug}].");

            return self::FAILURE;
        }

        try
        {
            EnablePlugin::call(slug: $slug);
        }
        catch (PluginMigrationException $e)
        {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Plugin [{$slug}] enabled.");

        return self::SUCCESS;
    }
}
