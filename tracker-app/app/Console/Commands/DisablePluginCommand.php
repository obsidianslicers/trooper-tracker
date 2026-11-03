<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\DisablePlugin;
use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class DisablePluginCommand extends Command
{
    protected $signature = 'plugins:disable {slug : The plugin slug from plugins:list}';

    protected $description = 'Disable a plugin for the whole site. Its data and settings are kept.';

    public function handle(PluginManager $plugins): int
    {
        $slug = (string) $this->argument('slug');

        if ($plugins->find($slug) === null)
        {
            $this->error("No plugin found with slug [{$slug}].");

            return self::FAILURE;
        }

        DisablePlugin::call(slug: $slug);

        $this->info("Plugin [{$slug}] disabled.");

        return self::SUCCESS;
    }
}
