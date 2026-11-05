<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\PluginChangeAction;
use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Models\PluginChange;
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

    /**
     * Enabled plugins that were not loaded this request because something they
     * need is missing.
     *
     * @var array<string, true>
     */
    private array $skipped = [];

    public function __construct(
        private readonly Application $app,
        private readonly PluginDiscovery $discovery,
        private readonly PluginAutoloader $autoloader,
        private readonly PluginRequirements $requirements = new PluginRequirements,
    ) {}

    /**
     * @return array<string, PluginManifest>
     */
    public function all(): array
    {
        return $this->manifests ??= $this->discovery->discover();
    }

    /**
     * Forget the discovered plugins so the next lookup scans the folders again,
     * for example after a plugin is uploaded or removed.
     */
    public function forgetManifests(): void
    {
        $this->manifests = null;
    }

    public function find(string $slug): ?PluginManifest
    {
        return $this->all()[$slug] ?? null;
    }

    public function findOrFail(string $slug): PluginManifest
    {
        return $this->find($slug)
            ?? throw new InvalidArgumentException("Unknown plugin [{$slug}].");
    }

    public function isEnabled(string $slug): bool
    {
        if ($this->find($slug) === null)
        {
            return false;
        }

        return (bool) ($this->rows()[$slug][Plugin::IS_ENABLED] ?? false);
    }

    /**
     * Turned on by an administrator, waiting for a deploy to install its packages.
     */
    public function isEnablePending(string $slug): bool
    {
        return (bool) ($this->rows()[$slug][Plugin::IS_ENABLE_PENDING] ?? false);
    }

    /**
     * Remember where a plugin's files came from, such as an upload.
     */
    public function recordSource(string $slug, PluginSource $source): Plugin
    {
        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $record->source = $source->value;
        $record->save();

        $this->forgetState();

        return $record;
    }

    /**
     * Record what a deploy or a change found for the plugin. Saves only when it
     * differs, so a deploy that changes nothing leaves the row alone. Returns
     * whether it changed.
     */
    public function recordStatus(string $slug, PluginStatus $status, ?string $error = null): bool
    {
        $record = Plugin::query()->where(Plugin::SLUG, $slug)->first();

        $unchanged = $record?->status === $status->value && $record->last_error === $error;

        if ($record === null || $unchanged)
        {
            return false;
        }

        $record->status = $status->value;
        $record->last_error = $error;
        $record->checked_at = now();
        $record->save();

        $this->forgetState();

        return true;
    }

    /**
     * Mark a plugin to be turned on by the next deploy, once its packages are in.
     */
    public function markEnablePending(string $slug): Plugin
    {
        $manifest = $this->findOrFail($slug);

        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $record->is_enable_pending = true;
        $record->save();

        PluginChange::record(
            $slug,
            PluginChangeAction::ENABLE_QUEUED,
            $record->installed_version,
            $manifest->version,
        );

        $this->stateChanged();

        return $record;
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
        return array_filter(
            $this->all(),
            fn (PluginManifest $manifest): bool => $this->isEnabled($manifest->slug),
        );
    }

    /**
     * Load every enabled plugin, the plugins each one needs first. An enabled
     * plugin whose requirements are no longer met, for example after a PHP
     * change, is skipped and logged instead of loaded.
     */
    public function loadEnabledPlugins(): void
    {
        foreach ($this->enabled() as $manifest)
        {
            $this->loadWithRequirements($manifest, []);
        }
    }

    /**
     * @param  array<int, string>  $loading  slugs further up this chain, to stop loops
     */
    private function loadWithRequirements(PluginManifest $manifest, array $loading): bool
    {
        $slug = $manifest->slug;

        if ($this->isLoaded($slug) || isset($this->skipped[$slug]))
        {
            return $this->isLoaded($slug);
        }

        if (in_array($slug, $loading, true))
        {
            return $this->skip($manifest, 'its required plugins need each other in a loop');
        }

        foreach (array_keys($manifest->plugin_requires) as $required)
        {
            $required_manifest = $this->enabled()[$required] ?? null;

            if ($required_manifest === null
                || !$this->loadWithRequirements($required_manifest, [...$loading, $slug]))
            {
                return $this->skip($manifest, "the [{$required}] plugin did not load");
            }
        }

        $unmet = $this->unmetRequirements($slug);

        return $unmet === []
            ? $this->load($manifest)
            : $this->skip($manifest, 'it needs '.implode('; ', $unmet));
    }

    private function skip(PluginManifest $manifest, string $reason): bool
    {
        $this->skipped[$manifest->slug] = true;
        Log::warning("Plugin [{$manifest->slug}] was not loaded because {$reason}.");

        return false;
    }

    /**
     * What the plugin needs that the site does not have: PHP, extensions, packages,
     * the plugin API version, and other plugins enabled at the right version.
     *
     * @return array<int, string> one description per unmet requirement
     */
    public function unmetRequirements(string $slug): array
    {
        $manifest = $this->findOrFail($slug);
        $requires = [...$this->composerRequires($manifest), ...$manifest->requires];

        return [
            ...$this->requirements->unmet($requires),
            ...$this->unmetPluginRequirements($manifest),
        ];
    }

    /**
     * A plugin installed through Composer had its composer.json checked by
     * Composer. A plugin folder's composer.json is checked here.
     *
     * @return array<string, string>
     */
    private function composerRequires(PluginManifest $manifest): array
    {
        $require = $manifest->composerJson()['require'] ?? [];

        return is_array($require) ? $require : [];
    }

    /**
     * How to install the packages a plugin folder needs, when some are missing.
     */
    private function installHint(PluginManifest $manifest): ?string
    {
        $name = $manifest->composerJson()['name'] ?? null;
        $missing = $this->requirements->unmet($this->composerRequires($manifest));

        if (!is_string($name) || $missing === [])
        {
            return null;
        }

        return "Install its packages with a deploy: composer require {$name}";
    }

    /**
     * @return array<int, string>
     */
    private function unmetPluginRequirements(PluginManifest $manifest): array
    {
        $unmet = [];

        foreach ($manifest->plugin_requires as $slug => $constraint)
        {
            $unmet[] = $this->unmetPluginRequirement($slug, $constraint);
        }

        return array_values(array_filter($unmet));
    }

    private function unmetPluginRequirement(string $slug, string $constraint): ?string
    {
        $required = $this->find($slug);

        if ($required === null)
        {
            return "the [{$slug}] plugin {$constraint}, which is not installed";
        }

        if (!$this->requirements->satisfies($required->version, $constraint))
        {
            return "the {$required->name} plugin {$constraint} ({$required->version} is installed)";
        }

        return $this->isEnabled($slug) ? null : "the {$required->name} plugin turned on first";
    }

    /**
     * Names of the enabled plugins that need this one.
     *
     * @return array<int, string>
     */
    public function dependents(string $slug): array
    {
        $dependents = array_filter(
            $this->enabled(),
            fn (PluginManifest $manifest): bool => isset($manifest->plugin_requires[$slug]),
        );

        return array_values(array_map(
            fn (PluginManifest $manifest): string => $manifest->name,
            $dependents,
        ));
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
            Log::error(
                "Plugin [{$manifest->slug}] failed to load: {$e->getMessage()}",
                ['exception' => $e],
            );

            return false;
        }
    }

    /**
     * Check the plugin's requirements, run its migrations and turn it on. If
     * something it needs is missing nothing runs; if a migration fails, the ones
     * that ran are rolled back. Either way the plugin stays off.
     *
     * @throws PluginException
     */
    public function enable(string $slug): Plugin
    {
        $manifest = $this->findOrFail($slug);
        $from_version = $this->record($slug)?->installed_version;
        $this->checkRequirements($manifest, $from_version);
        $migrations = $this->migrateOrRecordFailure($manifest, $from_version);

        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $record->is_enabled = true;
        $record->is_enable_pending = false;
        $record->installed_version = $manifest->version;
        $record->enabled_at = now();
        $record->save();

        PluginChange::record(
            $slug,
            PluginChangeAction::ENABLED,
            $from_version,
            $manifest->version,
            $migrations,
        );

        $this->stateChanged();
        $this->runEnabledHook($manifest);

        return $record;
    }

    /**
     * Whether the plugin's files are a different version from the one whose
     * migrations last ran, for example after a deploy brought new files.
     */
    public function hasPendingUpgrade(string $slug): bool
    {
        $installed = $this->record($slug)?->installed_version;

        return $this->isEnabled($slug)
            && $installed !== null
            && $installed !== $this->findOrFail($slug)->version;
    }

    /**
     * Bring an enabled plugin's database up to its current files: run new
     * migrations and record the new version. Settings and data are kept.
     *
     * @throws PluginException
     */
    public function upgrade(string $slug): Plugin
    {
        $manifest = $this->findOrFail($slug);
        $record = $this->record($slug) ?? throw new InvalidArgumentException(
            "Plugin [{$slug}] is not installed."
        );
        $from_version = $record->installed_version;
        $this->checkRequirements($manifest, $from_version);
        $migrations = $this->migrateOrRecordFailure($manifest, $from_version);

        $record->installed_version = $manifest->version;
        $record->save();

        PluginChange::record(
            $slug,
            PluginChangeAction::UPGRADED,
            $from_version,
            $manifest->version,
            $migrations,
        );

        $this->stateChanged();

        return $record;
    }

    /**
     * @return array<int, string>
     *
     * @throws PluginMigrationException
     */
    private function migrateOrRecordFailure(PluginManifest $manifest, ?string $from_version): array
    {
        try
        {
            return $this->migrator()->migrate($manifest);
        }
        catch (PluginMigrationException $e)
        {
            PluginChange::record(
                $manifest->slug,
                PluginChangeAction::FAILED,
                $from_version,
                $manifest->version,
                message: $e->getMessage(),
            );

            throw $e;
        }
    }

    /**
     * @throws PluginRequirementsException
     */
    private function checkRequirements(PluginManifest $manifest, ?string $from_version): void
    {
        $unmet = $this->unmetRequirements($manifest->slug);

        if ($unmet === [])
        {
            return;
        }

        $hint = $this->installHint($manifest);
        $e = PluginRequirementsException::cannotEnable($manifest, $unmet, $hint);
        PluginChange::record(
            $manifest->slug,
            PluginChangeAction::FAILED,
            $from_version,
            $manifest->version,
            message: $e->getMessage(),
        );

        throw $e;
    }

    /**
     * @throws PluginRequirementsException
     */
    private function checkNotNeeded(PluginManifest $manifest): void
    {
        $dependents = $this->dependents($manifest->slug);

        if ($dependents !== [])
        {
            throw PluginRequirementsException::stillNeeded($manifest, $dependents);
        }
    }

    private function runEnabledHook(PluginManifest $manifest): void
    {
        if (!$this->load($manifest))
        {
            return;
        }

        try
        {
            $this->loaded[$manifest->slug]->pluginEnabled();
        }
        catch (Throwable $e)
        {
            Log::error(
                "Plugin [{$manifest->slug}] failed while being enabled: {$e->getMessage()}",
                ['exception' => $e],
            );
        }
    }

    /**
     * @throws PluginRequirementsException when an enabled plugin still needs it
     */
    public function disable(string $slug): Plugin
    {
        $this->checkNotNeeded($this->findOrFail($slug));

        $record = Plugin::firstOrNew([Plugin::SLUG => $slug]);
        $record->is_enabled = false;
        $record->is_enable_pending = false;
        $record->save();

        PluginChange::record($slug, PluginChangeAction::DISABLED, $record->installed_version);

        $this->stateChanged();

        return $record;
    }

    /**
     * Turn a plugin off and remove everything it stored: its migrations are rolled
     * back (dropping its tables) and its settings deleted. The plugin's files stay,
     * so it can be enabled again; remove them with the next deploy.
     *
     * @throws PluginRequirementsException when an enabled plugin still needs it
     */
    public function uninstall(string $slug): void
    {
        $manifest = $this->findOrFail($slug);
        $this->checkNotNeeded($manifest);
        $migrations = $this->migrator()->ran($manifest);

        $this->migrator()->reset($manifest);

        PluginChange::record(
            $slug,
            PluginChangeAction::UNINSTALLED,
            $this->record($slug)?->installed_version,
            migrations: $migrations,
        );
        Plugin::query()->where(Plugin::SLUG, $slug)->delete();

        $this->stateChanged();
    }

    public function isInstalled(string $slug): bool
    {
        return isset($this->rows()[$slug]);
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

    private function migrator(): PluginMigrator
    {
        return $this->app->make(PluginMigrator::class);
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
     * Keep the packages the next deploy installs in step with what is enabled.
     */
    private function saveDeployRequirements(): void
    {
        try
        {
            $this->app->make(PluginDeployPackages::class)->writeRequirements();
        }
        catch (Throwable $e)
        {
            Log::warning("Could not save plugin packages for the deploy: {$e->getMessage()}");
        }
    }

    /**
     * Rebuild framework caches so the change reaches every request and queue worker,
     * not just this one.
     */
    private function stateChanged(): void
    {
        $this->forgetState();
        $this->saveDeployRequirements();

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
