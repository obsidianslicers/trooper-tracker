<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\UpgradePlugin;
use App\Plugins\PluginException;
use App\Plugins\PluginManager;
use Illuminate\Console\Command;

class UpgradePluginsCommand extends Command
{
    protected $signature = 'plugins:upgrade {slug? : Only this plugin}';

    protected $description = 'Run new migrations for enabled plugins whose files changed version.';

    public function handle(PluginManager $plugins): int
    {
        $slugs = $this->pendingSlugs($plugins);

        if ($slugs === [])
        {
            $this->info('Every enabled plugin is up to date.');

            return self::SUCCESS;
        }

        $failed = false;

        foreach ($slugs as $slug)
        {
            $failed = !$this->upgrade($plugins, $slug) || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function pendingSlugs(PluginManager $plugins): array
    {
        $slug = $this->argument('slug');
        $slugs = $slug === null ? array_keys($plugins->enabled()) : [(string) $slug];

        return array_values(array_filter(
            $slugs,
            fn (string $slug): bool => $plugins->find($slug) !== null
                && $plugins->hasPendingUpgrade($slug),
        ));
    }

    private function upgrade(PluginManager $plugins, string $slug): bool
    {
        $from = $plugins->record($slug)?->installed_version;

        try
        {
            $record = UpgradePlugin::call(slug: $slug);
        }
        catch (PluginException $e)
        {
            $this->error($e->getMessage());

            return false;
        }

        $this->info("Plugin [{$slug}] upgraded from {$from} to {$record->installed_version}.");

        return true;
    }
}
