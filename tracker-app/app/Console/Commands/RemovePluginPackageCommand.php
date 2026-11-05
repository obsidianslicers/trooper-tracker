<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\RemovePluginPackage;
use App\Plugins\PluginException;
use Illuminate\Console\Command;

class RemovePluginPackageCommand extends Command
{
    protected $signature = 'plugins:remove-package
        {name : The Composer package to stop installing}';

    protected $description = 'Remove a Composer-managed plugin with the next deploy.';

    public function handle(): int
    {
        $name = strtolower((string) $this->argument('name'));

        try
        {
            RemovePluginPackage::call(name: $name);
        }
        catch (PluginException $e)
        {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$name} will be removed by the next deploy.");

        return self::SUCCESS;
    }
}
