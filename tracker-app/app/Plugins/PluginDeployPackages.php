<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\PackageUpdatePolicy;
use App\Enums\PluginSource;
use App\Models\PluginPackage;
use Composer\InstalledVersions;
use Throwable;

/**
 * Works out which Composer packages the site's plugins need, for the deploy.
 *
 * A plugin folder with its own composer.json is installed as a Composer package
 * through a path repository; a plugin from another repository is a package an
 * administrator added. The app's composer.json never lists them. Instead this
 * class saves them to storage/app/plugin-deploy/requirements.json, and the
 * deploy builds composer.plugins.json from it (see Deploy\ManagedComposerFile).
 * Uploaded zips never get packages: they may only use what the app has.
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
        if ($this->plugins->record($manifest->slug)?->source === PluginSource::UPLOADED->value)
        {
            return null;
        }

        if (!$this->isPluginFolder($manifest))
        {
            return null;
        }

        $composer = $this->composerFile($manifest);
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
            $this->packages($this->composerFile($manifest))
        );

        return $missing_packages !== []
            && count($missing_packages) === count($this->plugins->unmetRequirements($slug));
    }

    /**
     * Each package the deploy should install, with the version constraint it
     * should use, e.g. "acme/badges:^1.0", for display and plugins:packages.
     *
     * @return array<int, string>
     */
    public function toInstall(): array
    {
        $packages = [];

        foreach ($this->requirements()['packages'] as $name => $constraint)
        {
            $packages[] = $constraint === '*' ? $name : "{$name}:{$constraint}";
        }

        return $packages;
    }

    /**
     * @return array{packages: array<string, string>, repositories: array<int, array<string, mixed>>}
     */
    public function requirements(): array
    {
        $packages = [];
        $repositories = [];

        foreach ($this->bundled() as $name => $manifest)
        {
            $packages[$name] = '*';
            $repositories[] = $this->pathRepository($manifest);
        }

        foreach ($this->added() as $package)
        {
            $packages[$package->name] = $this->constraintFor($package);
            $repositories[] = $package->repository_url === null
                ? null
                : ['type' => 'vcs', 'url' => $package->repository_url];
        }

        ksort($packages);

        return [
            'packages' => $packages,
            'repositories' => array_values(array_unique(array_filter($repositories), SORT_REGULAR)),
        ];
    }

    /**
     * Save the requirements for the next deploy. Returns whether they changed.
     */
    public function writeRequirements(): bool
    {
        $path = self::requirementsPath();
        $json = json_encode($this->requirements(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

        if (is_file($path) && file_get_contents($path) === $json)
        {
            return false;
        }

        if (!is_dir(dirname($path)))
        {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, $json);

        return true;
    }

    public static function requirementsPath(): string
    {
        return config('plugins.deploy_path').'/requirements.json';
    }

    /**
     * The version a deploy asks Composer for. A pinned package keeps the version
     * it has; a compatible one may move up within its major version. Neither ever
     * jumps to a new major version unless the administrator's constraint says so,
     * and a constraint the installed version no longer meets wins.
     */
    public function constraintFor(PluginPackage $package): string
    {
        $installed = $package->installed_version;
        $constraint = $package->version_constraint;

        if ($installed === null)
        {
            return $constraint ?? '*';
        }

        if ($constraint !== null && !$this->requirements->satisfies($installed, $constraint))
        {
            return $constraint;
        }

        if ($package->update_policy !== PackageUpdatePolicy::COMPATIBLE->value)
        {
            return $installed;
        }

        return $constraint ?? $this->sameMajor($installed);
    }

    private function sameMajor(string $version): string
    {
        $parts = explode('.', ltrim($version, 'v'));

        return $parts[0] === '0' && isset($parts[1])
            ? "^0.{$parts[1]}"
            : '^'.$parts[0].'.0';
    }

    /**
     * A plugin folder outside tracker-app/plugins, such as one copied into the
     * persistent plugins folder, needs its own path repository.
     *
     * @return array<string, mixed>|null
     */
    private function pathRepository(PluginManifest $manifest): ?array
    {
        $folder = dirname($manifest->path);

        $local = $this->discovery->localPath();

        if ((realpath($folder) ?: $folder) === (realpath($local) ?: $local))
        {
            return null;
        }

        return ['type' => 'path', 'url' => $manifest->path, 'options' => ['symlink' => true]];
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
     * @return array<int, PluginPackage>
     */
    private function added(): array
    {
        try
        {
            return PluginPackage::query()->orderBy(PluginPackage::NAME)->get()->all();
        }
        catch (Throwable)
        {
            // The table does not exist yet, before this deploy's migrations.
            return [];
        }
    }

    /**
     * Plugin folders with packages of their own that are on or waiting.
     *
     * @return array<string, PluginManifest> keyed by Composer name
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
                $names[$name] = $manifest;
            }
        }

        return $names;
    }

    /**
     * A folder under tracker-app/plugins or the persistent plugins folder, even
     * once Composer has installed it through a path repository.
     */
    private function isPluginFolder(PluginManifest $manifest): bool
    {
        $path = realpath($manifest->path) ?: $manifest->path;
        $folders = array_filter([$this->discovery->localPath(), $this->discovery->uploadsPath()]);

        foreach ($folders as $folder)
        {
            if (str_starts_with($path, (realpath($folder) ?: $folder).DIRECTORY_SEPARATOR))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function composerFile(PluginManifest $manifest): array
    {
        $file = $manifest->path.'/composer.json';
        $composer = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($composer) ? $composer : [];
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
        $unmet = $this->plugins->unmetRequirements($slug);

        // Still waiting: leave it pending without logging another failed attempt.
        if ($unmet !== [])
        {
            return 'It needs '.implode('; ', $unmet).'.';
        }

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
