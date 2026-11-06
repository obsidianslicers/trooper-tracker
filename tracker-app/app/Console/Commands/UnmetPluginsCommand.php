<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginManager;
use App\Plugins\PluginRequirements;
use Illuminate\Console\Command;
use Throwable;

/**
 * Lists the enabled plugins that can't run right now, one slug per line. deploy.sh
 * runs it before and after installing packages, and stops the deploy if a plugin
 * that worked before would stop working. A plugin with an uploaded update whose
 * requirements are met is fine: the deploy applies that update.
 */
class UnmetPluginsCommand extends Command
{
    protected $signature = 'plugins:unmet';

    protected $description = 'List enabled plugins whose files or requirements are missing.';

    public function handle(PluginManager $plugins, PluginDiscovery $discovery): int
    {
        foreach ($this->enabledSlugs() as $slug)
        {
            $can_run = $plugins->find($slug) !== null
                && $plugins->unmetRequirements($slug) === [];

            if (!$can_run && !$this->canRunStaged($discovery, $slug))
            {
                $this->line($slug);
            }
        }

        return self::SUCCESS;
    }

    private function canRunStaged(PluginDiscovery $discovery, string $slug): bool
    {
        $staged = $discovery->staged($slug);
        $requires = $staged?->composerJson()['require'] ?? [];

        return $staged !== null && (new PluginRequirements)->unmet([
            ...(is_array($requires) ? $requires : []),
            ...$staged->requires,
        ]) === [];
    }

    /**
     * @return array<int, string>
     */
    private function enabledSlugs(): array
    {
        try
        {
            return Plugin::query()->where(Plugin::IS_ENABLED, true)->pluck(Plugin::SLUG)->all();
        }
        catch (Throwable)
        {
            return [];
        }
    }
}
