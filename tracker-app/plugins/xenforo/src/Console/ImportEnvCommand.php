<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Console;

use Illuminate\Console\Command;
use TroopTracker\Plugins\Xenforo\Messages\ImportXenforoEnv;

class ImportEnvCommand extends Command
{
    protected $signature = 'xenforo:import-env
        {--force : Replace settings that are already saved}';

    protected $description = 'Copy the XenForo .env values onto the plugin settings page.';

    public function handle(): int
    {
        $copied = ImportXenforoEnv::call(overwrite: (bool) $this->option('force'));

        if ($copied === [])
        {
            $this->info('Nothing to copy: the settings are saved or the .env values are empty.');

            return self::SUCCESS;
        }

        $this->info('Copied to the XenForo settings: '.implode(', ', $copied).'.');
        $this->line('The XENFORO_* and TRACKER_REQUIRE_XENFORO lines can now leave .env.');

        return self::SUCCESS;
    }
}
