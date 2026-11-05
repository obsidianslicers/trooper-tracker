<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\MakePlugin;
use Illuminate\Console\Command;
use InvalidArgumentException;

class MakePluginCommand extends Command
{
    protected $signature = 'make:plugin
        {name : The plugin name, e.g. "Event Weather"}
        {--namespace= : PHP namespace (default: TroopTracker\\Plugins\\<Name>)}
        {--composer : Also write a composer.json for the Composer packages it needs}';

    protected $description = 'Create a starter plugin in the plugins folder.';

    public function handle(): int
    {
        try
        {
            $path = MakePlugin::call(
                name: (string) $this->argument('name'),
                namespace: $this->option('namespace'),
                with_composer: (bool) $this->option('composer'),
            );
        }
        catch (InvalidArgumentException $e)
        {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Plugin created at [{$path}].");
        $this->line('Enable it with: php artisan plugins:enable '.basename($path));

        return self::SUCCESS;
    }
}
