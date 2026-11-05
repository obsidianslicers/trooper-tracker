<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Models\PluginPackage;
use Composer\InstalledVersions;
use Throwable;

/**
 * Works out which bundled plugins need Composer packages installed by the deploy.
 *
 * A bundled plugin with its own composer.json is installed with
 * "composer require <its name>". The app's composer.json never lists it, so the
 * deploy asks this class for the plugins that are on, or waiting to be turned
 * on, and requires them after "composer install".
 */
final class PluginDeployPackages
{
    public function __construct(
        private readonly PluginManager $plugins,
        private readonly PluginDiscovery $discovery,
        private readonly PluginRequirements $requirements = new PluginRequirements,
    ) {}

    /**
     * The Composer name of a bundled plugin that needs packages of its own.
     */
    public function packageName(PluginManifest $manifest): ?string
    {
        if ($this->discovery->isUploaded($manifest))
        {
            return null;
        }

        $composer = $manifest->composerJson();
        $name = $composer['name'] ?? null;

        return is_string($name) && $this->packages($composer) !== [] ? $name : null;
    }

    /**
     * Whether the only thing stopping the plugin is packages a deploy can install.
     */
    public function canInstallOnDeploy(string $slug): bool
    {
        $manifest = $this->plugins->findOrFail($slug);

        if ($this->packageName($manifest) === null)
        {
            return false;
        }

        $missing_packages = $this->requirements->unmet(
            $this->packages($manifest->composerJson())
        );

        return $missing_packages !== []
            && count($missing_packages) === count($this->plugins->unmetRequirements($slug));
    }

    /**
     * Package names for the deploy to require: bundled plugins that are on or
     * waiting, and the Composer-managed plugins administrators added.
     *
     * @return array<int, string>
     */
    public function toInstall(): array
    {
        return array_values(array_unique([...$this->bundled(), ...$this->added()]));
    }

    /**
     * The installed Composer plugin that came from this package, if any.
     */
    public function manifestFromPackage(string $name): ?PluginManifest
    {
        $path = InstalledVersions::isInstalled($name)
            ? InstalledVersions::getInstallPath($name)
            : null;
        $path = $path === null ? null : (realpath($path) ?: $path);

        foreach ($this->plugins->all() as $manifest)
        {
            if ($path !== null && $manifest->is_composer_package
                && (realpath($manifest->path) ?: $manifest->path) === $path)
            {
                return $manifest;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function added(): array
    {
        try
        {
            return PluginPackage::query()
                ->orderBy(PluginPackage::NAME)
                ->get()
                ->map(fn (PluginPackage $package): string => $package->requirement())
                ->all();
        }
        catch (Throwable)
        {
            // The table does not exist yet, before this deploy's migrations.
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function bundled(): array
    {
        $names = [];

        foreach ($this->plugins->all() as $manifest)
        {
            $wanted = $this->plugins->isEnabled($manifest->slug)
                || $this->plugins->isEnablePending($manifest->slug);
            $name = $wanted ? $this->packageName($manifest) : null;

            if ($name !== null)
            {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Turn on each waiting plugin whose packages are now installed.
     *
     * @return array<string, ?string> slug => null when enabled, or why it wasn't
     */
    public function enablePending(): array
    {
        $results = [];

        foreach ($this->plugins->all() as $manifest)
        {
            if ($this->plugins->isEnablePending($manifest->slug))
            {
                $results[$manifest->slug] = $this->tryEnable($manifest->slug);
            }
        }

        return $results;
    }

    private function tryEnable(string $slug): ?string
    {
        try
        {
            $this->plugins->enable($slug);

            return null;
        }
        catch (Throwable $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * The "require" entries that are real packages, not PHP or its extensions.
     *
     * @param  array<string, mixed>  $composer
     * @return array<string, string>
     */
    private function packages(array $composer): array
    {
        $require = is_array($composer['require'] ?? null) ? $composer['require'] : [];

        return array_filter(
            $require,
            fn (string $package): bool => $package !== 'php'
                && !str_starts_with($package, 'ext-')
                && !str_starts_with($package, 'lib-'),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
