<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\UninstallPlugin;
use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class UninstallPluginCommand extends Command
{
    protected $signature = 'plugins:uninstall
        {slug : The plugin slug from plugins:list}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Disable a plugin and delete its tables and settings.';

    public function handle(PluginManager $plugins): int
    {
        $slug = (string) $this->argument('slug');

        if ($plugins->find($slug) === null)
        {
            $this->error("No plugin found with slug [{$slug}].");

            return self::FAILURE;
        }

        $question = "Uninstall [{$slug}]? "
            .'This deletes its tables and settings and cannot be undone.';

        if (!$this->option('force') && !$this->confirm($question))
        {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        UninstallPlugin::call(slug: $slug);

        $this->info("Plugin [{$slug}] uninstalled. Remove its files with your next deploy.");

        return self::SUCCESS;
    }
}
