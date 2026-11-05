<?php

declare(strict_types=1);

namespace App\Plugins\Deploy;

/**
 * Builds composer.plugins.json: the app's own composer.json plus the packages the
 * site's plugins need, with every core package held at the version in the app's
 * composer.lock. The deploy installs from that file and its own lock file,
 * composer.plugins.lock, which stays on the server between deploys. So:
 *
 * - core packages always match the release being deployed;
 * - plugin packages keep the versions they had, unless their constraint allows a
 *   compatible update or the plugin itself changed;
 * - the app's composer.json and composer.lock are never edited.
 *
 * This class must not use the framework: the deploy runs it before Composer has
 * installed the release's packages (see bin/plugin-composer).
 */
final class ManagedComposerFile
{
    public const FILE = 'composer.plugins.json';

    public const LOCK = 'composer.plugins.lock';

    /**
     * @param  array<string, mixed>  $app_json  the app's composer.json
     * @param  array<string, mixed>  $app_lock  the app's composer.lock
     * @param  array<string, mixed>|null  $managed_lock  the last composer.plugins.lock
     * @param  array{packages?: array<string, string>, repositories?: array<int, array<string, mixed>>}  $requirements
     */
    public function __construct(
        private readonly array $app_json,
        private readonly array $app_lock,
        private readonly ?array $managed_lock,
        private readonly array $requirements,
    ) {}

    public function hasPluginPackages(): bool
    {
        return $this->pluginPackages() !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $json = $this->app_json;
        $json['require'] = [...($this->app_json['require'] ?? []), ...$this->pluginPackages()];
        $json['repositories'] = [
            ...($this->app_json['repositories'] ?? []),
            ...($this->requirements['repositories'] ?? []),
        ];

        // Only the Composer plugins the app trusts may run; a plugin's own
        // dependencies cannot add more.
        $json['config']['allow-plugins'] = $this->app_json['config']['allow-plugins'] ?? false;

        return $this->withAppPins($json);
    }

    /**
     * Packages "composer update" must touch: the plugin packages, and core
     * packages whose version in this release differs from the managed lock.
     *
     * @return array<int, string>
     */
    public function packagesToUpdate(): array
    {
        $names = array_keys($this->pluginPackages());

        if ($this->managed_lock !== null)
        {
            $managed = $this->lockedVersions($this->managed_lock);

            foreach ($this->lockedVersions($this->app_lock) as $name => $version)
            {
                if (($managed[$name] ?? null) !== $version)
                {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Core packages the managed lock has at a different version from the app's
     * lock (or not at all), e.g. "laravel/framework v12.3.0 -> v12.4.0".
     *
     * @return array<int, string>
     */
    public function movedCorePackages(): array
    {
        $managed = $this->lockedVersions($this->managed_lock ?? []);
        $moved = [];

        foreach ($this->lockedVersions($this->app_lock, without_paths: true) as $name => $version)
        {
            if (($managed[$name] ?? null) !== $version)
            {
                $moved[] = "{$name} {$version} -> ".($managed[$name] ?? 'missing');
            }
        }

        return $moved;
    }

    /**
     * @return array<string, string>
     */
    private function pluginPackages(): array
    {
        $core = $this->lockedVersions($this->app_lock);

        return array_filter(
            $this->requirements['packages'] ?? [],
            fn (string $name): bool => !isset($core[$name]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * A root requirement is replaced by the exact version the app locked, so a
     * plugin can never move a core package.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function withAppPins(array $json): array
    {
        foreach (['packages' => 'require', 'packages-dev' => 'require-dev'] as $section => $key)
        {
            foreach ($this->pins($section) as $name => $pin)
            {
                $json[$key][$name] = $pin;
            }
        }

        return $json;
    }

    /**
     * @return array<string, string>
     */
    private function pins(string $section): array
    {
        $pins = [];

        foreach ($this->app_lock[$section] ?? [] as $package)
        {
            if (($package['dist']['type'] ?? null) === 'path')
            {
                continue;
            }

            $version = (string) $package['version'];
            $reference = $package['source']['reference'] ?? $package['dist']['reference'] ?? null;
            $pins[(string) $package['name']] = str_starts_with($version, 'dev-') && $reference
                ? "{$version}#{$reference}"
                : $version;
        }

        return $pins;
    }

    /**
     * @param  array<string, mixed>  $lock
     * @return array<string, string>
     */
    private function lockedVersions(array $lock, bool $without_paths = false): array
    {
        $versions = [];

        foreach ([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])] as $package)
        {
            if ($without_paths && ($package['dist']['type'] ?? null) === 'path')
            {
                continue;
            }

            $versions[(string) $package['name']] = (string) $package['version'];
        }

        return $versions;
    }
}
