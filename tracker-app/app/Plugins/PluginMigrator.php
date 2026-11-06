<?php

declare(strict_types=1);

namespace App\Plugins;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs a plugin's migrations as one unit: if any of them fails, the ones this
 * run already finished are rolled back, so the plugin is left as it was.
 *
 * MySQL cannot roll back a half-run migration's DDL, so a migration should start
 * by dropping anything an earlier failed run of it may have left behind.
 */
final class PluginMigrator
{
    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return array<int, string> The migrations that ran.
     *
     * @throws PluginMigrationException
     */
    public function migrate(PluginManifest $manifest): array
    {
        if (!is_dir($manifest->migrationsPath()))
        {
            return [];
        }

        $this->ensureRepository();
        $before = $this->ran($manifest);

        try
        {
            $this->migrator->run([$manifest->migrationsPath()]);
        }
        catch (Throwable $e)
        {
            $this->undo($manifest, $before, $e);
        }

        return array_values(array_diff($this->ran($manifest), $before));
    }

    /**
     * Roll back every migration the plugin has run, newest first.
     */
    public function reset(PluginManifest $manifest): void
    {
        if (is_dir($manifest->migrationsPath()) && $this->migrator->repositoryExists())
        {
            $this->migrator->reset([$manifest->migrationsPath()]);
        }
    }

    /**
     * @return array<int, string> Names of the plugin's migrations that have run.
     */
    public function ran(PluginManifest $manifest): array
    {
        if (!$this->migrator->repositoryExists())
        {
            return [];
        }

        $names = array_keys($this->migrator->getMigrationFiles([$manifest->migrationsPath()]));

        return array_values(array_intersect($this->migrator->getRepository()->getRan(), $names));
    }

    /**
     * @param  array<int, string>  $before
     *
     * @throws PluginMigrationException
     */
    private function undo(PluginManifest $manifest, array $before, Throwable $e): never
    {
        $finished = array_diff($this->ran($manifest), $before);

        Log::error("Plugin [{$manifest->slug}] migration failed: {$e->getMessage()}", [
            'rolled_back' => array_values($finished),
            'exception' => $e,
        ]);

        // The finished ones are the newest batch, so a plain rollback undoes exactly them.
        if ($finished !== [])
        {
            $this->migrator->rollback([$manifest->migrationsPath()]);
        }

        throw new PluginMigrationException(
            "{$manifest->name} could not update its database, so nothing was changed: "
            .$e->getMessage(),
            previous: $e,
        );
    }

    private function ensureRepository(): void
    {
        if (!$this->migrator->repositoryExists())
        {
            $this->migrator->getRepository()->createRepository();
        }
    }
}
