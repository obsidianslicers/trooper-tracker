<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Models\Plugin;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Discovers plugins, tracks which ones are enabled, and loads the enabled ones.
 *
 * Enabled state and settings live in the tt_plugins table. The rows are cached
 * (still encrypted) so loading plugins does not query the database per request.
 */
class PluginManager
{
    public const CACHE_KEY = 'plugins.state';

    private const TABLE = 'tt_plugins';

    /**
     * @var array<string, PluginManifest>|null
     */
    private ?array $manifests = null;

    /**
     * Raw tt_plugins rows keyed by slug. Kept as arrays because plugins load before
     * Eloquent is booted, and booting a model that early would lose its events.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $rows = null;

    /**
     * @var array<string, PluginServiceProvider>
     */
    private array $loaded = [];

    public function __construct(
        private readonly Application $app,
        private readonly PluginDiscovery $discovery,
        private readonly PluginAutoloader $autoloader,
    ) {}

    /**
     * @return array<string, PluginManifest>
     */
    public function all(): array
    {
        return $this->manifests ??= $this->discovery->discover();
    }

    public function find(string $slug): ?PluginManifest
    {
        return $this->all()[$slug] ?? null;
    }

    public function findOrFail(string $slug): PluginManifest
    {
        return $this->find($slug) ?? throw new InvalidArgumentException("Unknown plugin [{$slug}].");
    }

    public function isEnabled(string $slug): bool
    {
        return $this->find($slug) !== null && (bool) ($this->rows()[$slug][Plugin::IS_ENABLED] ?? false);
    }

    public function isLoaded(string $slug): bool
    {
        return isset($this->loaded[$slug]);
    }

    public function record(string $slug): ?Plugin
    {
        $row = $this->rows()[$slug] ?? null;

        return $row === null ? null : (new Plugin)->newFromBuilder($row);
    }

    /**
     * @return array<string, PluginManifest>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), fn (PluginManifest $manifest): bool => $this->isEnabled($manifest->slug));
    }

    public function loadEnabledPlugins(): void
    {
        foreach ($this->enabled() as $manifest)
        {
            $this->load($manifest);
        }
    }

    /**
     * Register a plugin's service provider. A plugin that fails to load is logged
     * and skipped so that one broken plugin cannot take the whole site down.
     */
    public function load(PluginManifest $manifest): bool
    {
        if ($this->isLoaded($manifest->slug))
        {
            return true;
        }

        try
        {
            $provider = $this->makeProvider($manifest);
            $this->app->register($provider);
            $this->loaded[$manifest->slug] = $provider;

            return true;
        }
        catch (Throwable $e)
        {
            Log::error("Plugin [{$manifest->slug}] failed to load: {$e->getMessage()}", ['exception' => $e]);

            return false;
        }
    }

    public function enable(string $slug): Plugin
    {
        $manifest = $this->findOrFail($slug);

        $this->migrate($manifest);

        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $record->is_enabled = true;
        $record->installed_version = $manifest->version;
        $record->enabled_at = now();
        $record->save();

        $this->stateChanged();

        return $record;
    }

    public function disable(string $slug): Plugin
    {
        $this->findOrFail($slug);

        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $record->is_enabled = false;
        $record->save();

        $this->stateChanged();

        return $record;
    }

    /**
     * The plugin's stored settings, with the manifest defaults filled in.
     *
     * @return array<string, mixed>
     */
    public function settings(string $slug): array
    {
        $manifest = $this->findOrFail($slug);
        $stored = $this->record($slug)?->settings ?? [];
        $settings = [];

        foreach ($manifest->settings as $setting)
        {
            $settings[$setting->key] = $stored[$setting->key] ?? $setting->default;
        }

        return $settings;
    }

    public function setting(string $slug, string $key, mixed $default = null): mixed
    {
        return $this->settings($slug)[$key] ?? $default;
    }

    /**
     * Save new setting values. Keys the manifest does not declare are ignored, and
     * an empty secret keeps the value already stored.
     *
     * @param  array<string, mixed>  $values
     */
    public function updateSettings(string $slug, array $values): Plugin
    {
        $manifest = $this->findOrFail($slug);
        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $settings = $record->settings ?? [];

        foreach ($manifest->settings as $setting)
        {
            $value = $values[$setting->key] ?? null;

            if ($setting->type === 'secret' && ($value === null || $value === ''))
            {
                continue;
            }

            $settings[$setting->key] = $setting->cast($value);
        }

        $record->settings = $settings;
        $record->save();

        $this->stateChanged();

        return $record;
    }

    /**
     * Run a plugin's migrations, if it ships any.
     */
    public function migrate(PluginManifest $manifest): void
    {
        if (!is_dir($manifest->migrationsPath()))
        {
            return;
        }

        Artisan::call('migrate', [
            '--path' => $manifest->migrationsPath(),
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    public function forgetState(): void
    {
        $this->rows = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function makeProvider(PluginManifest $manifest): PluginServiceProvider
    {
        if ($manifest->namespace !== null && !$manifest->is_composer_package)
        {
            $this->autoloader->add($manifest->namespace, $manifest->source_path);
        }

        if (!is_subclass_of($manifest->provider, PluginServiceProvider::class))
        {
            throw new InvalidArgumentException(
                "Provider [{$manifest->provider}] must extend ".PluginServiceProvider::class.'.'
            );
        }

        return new ($manifest->provider)($this->app, $manifest);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rows(): array
    {
        if ($this->rows !== null)
        {
            return $this->rows;
        }

        try
        {
            $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->app['db']
                ->table(self::TABLE)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all());
        }
        catch (Throwable)
        {
            // The plugins table does not exist yet (fresh install, or mid-migration).
            return $this->rows = [];
        }

        return $this->rows = array_column($rows, null, Plugin::SLUG);
    }

    /**
     * Rebuild framework caches so the change reaches every request and queue worker,
     * not just this one.
     */
    private function stateChanged(): void
    {
        $this->forgetState();

        if ($this->app->configurationIsCached())
        {
            Artisan::call('config:cache');
        }

        if ($this->app->routesAreCached())
        {
            Artisan::call('route:cache');
        }

        Artisan::call('queue:restart');
    }
}
