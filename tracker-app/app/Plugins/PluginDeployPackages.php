<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\PackageUpdatePolicy;
use App\Models\PluginPackage;
use Composer\InstalledVersions;
use Throwable;

/**
 * Works out which Composer packages the site's plugins need, for the deploy.
 *
 * A plugin folder (bundled, uploaded or copied onto the server) lists the
 * packages it needs in its composer.json; those of every plugin that is on or
 * waiting to be turned on are installed at app level. A plugin from another
 * repository is a package an administrator added. The app's composer.json never
 * lists them. Instead this class saves them to requirements.json in the plugin
 * deploy folder, and the deploy builds composer.plugins.json from it (see
 * Deploy\ManagedComposerFile).
 */
final class PluginDeployPackages
{
    public function __construct(
        private readonly PluginManager $plugins,
        private readonly PluginDiscovery $discovery,
        private readonly PluginRequirements $requirements = new PluginRequirements,
    ) {}

    /**
     * Whether the only thing stopping the plugin is packages a deploy can install.
     */
    public function canInstallOnDeploy(string $slug): bool
    {
        $manifest = $this->plugins->findOrFail($slug);
        $missing_packages = $this->requirements->unmet($manifest->packages());

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

        foreach ($this->wanted() as $manifest)
        {
            foreach ($manifest->packages() as $name => $constraint)
            {
                $packages[$name][] = $constraint;
            }
        }

        $packages = array_map($this->combine(...), $packages);

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
     * Two plugins asking for the same package must both be satisfied. A
     * constraint with "||" can't be combined safely, so the first one wins and
     * the requirement check reports the other plugin.
     *
     * @param  array<int, string>  $constraints
     */
    private function combine(array $constraints): string
    {
        $constraints = array_values(array_unique(array_map('trim', $constraints)));
        $specific = array_values(array_filter($constraints, fn (string $c): bool => $c !== '*'));
        $has_or = array_filter($specific, fn (string $c): bool => str_contains($c, '|')) !== [];

        return match (true)
        {
            $specific === [] => '*',
            count($specific) === 1 => $specific[0],
            $has_or => $specific[0],
            default => implode(' ', $specific),
        };
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
     * Plugin folders that are on or waiting to be turned on. An update that is
     * waiting for its packages counts instead of the installed version.
     *
     * @return array<int, PluginManifest>
     */
    private function wanted(): array
    {
        $wanted = [];

        foreach ($this->plugins->all() as $manifest)
        {
            if ($this->plugins->isEnabled($manifest->slug)
                || $this->plugins->isEnablePending($manifest->slug))
            {
                $wanted[] = $this->discovery->staged($manifest->slug) ?? $manifest;
            }
        }

        return $wanted;
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
}
