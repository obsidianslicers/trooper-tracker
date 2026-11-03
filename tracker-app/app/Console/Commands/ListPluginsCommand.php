<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\PageData\ListPluginsPageData;
use Illuminate\Console\Command;

class ListPluginsCommand extends Command
{
    protected $signature = 'plugins:list';

    protected $description = 'List every discovered plugin and whether it is enabled.';

    public function handle(): int
    {
        $rows = array_map(fn (array $plugin): array => [
            $plugin['manifest']->slug,
            $plugin['manifest']->name,
            $plugin['manifest']->version,
            $plugin['manifest']->is_composer_package ? 'composer' : 'local',
            $plugin['is_enabled'] ? 'yes' : 'no',
        ], ListPluginsPageData::call());

        if ($rows === [])
        {
            $this->info('No plugins found.');

            return self::SUCCESS;
        }

        $this->table(['Slug', 'Name', 'Version', 'Source', 'Enabled'], $rows);

        return self::SUCCESS;
    }
}
