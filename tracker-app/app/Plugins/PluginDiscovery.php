<?php

declare(strict_types=1);

namespace App\Plugins;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds plugins in the local plugins folder and in installed Composer packages.
 *
 * A local plugin is a folder holding a plugin.json file. A Composer plugin is a
 * package of type "trooptracker-plugin" with a plugin.json file at its root.
 */
class PluginDiscovery
{
    public const COMPOSER_TYPE = 'trooptracker-plugin';

    public const MANIFEST_FILE = 'plugin.json';

    public function __construct(
        private readonly string $local_path,
        private readonly ?string $uploads_path = null,
    ) {}

    public function uploadsPath(): ?string
    {
        return $this->uploads_path;
    }

    public function isUploaded(PluginManifest $manifest): bool
    {
        if ($this->uploads_path === null || $manifest->is_composer_package)
        {
            return false;
        }

        return dirname($manifest->path) === rtrim($this->uploads_path, '/');
    }

    /**
     * @return array<string, PluginManifest> Manifests keyed by slug.
     */
    public function discover(): array
    {
        $manifests = [];

        $composer_paths = $this->composerPaths();

        foreach ($composer_paths as $path)
        {
            $this->addManifest($manifests, $path, true);
        }

        foreach ($this->localPaths() as $path)
        {
            // A local folder required through the "plugins/*" path repository is
            // already listed as a Composer package.
            if (!in_array(realpath($path) ?: $path, $composer_paths, true))
            {
                $this->addManifest($manifests, $path, false);
            }
        }

        ksort($manifests);

        return $manifests;
    }

    /**
     * @return array<int, string>
     */
    private function localPaths(): array
    {
        $folders = array_filter([$this->local_path, $this->uploads_path]);
        $files = [];

        foreach ($folders as $folder)
        {
            $found = glob(rtrim($folder, '/').'/*/'.self::MANIFEST_FILE) ?: [];
            $files = [...$files, ...$found];
        }

        return array_map('dirname', $files);
    }

    /**
     * @return array<int, string>
     */
    private function composerPaths(): array
    {
        $paths = [];

        foreach (InstalledVersions::getInstalledPackagesByType(self::COMPOSER_TYPE) as $package)
        {
            $path = InstalledVersions::getInstallPath($package);

            if ($path !== null && is_file($path.'/'.self::MANIFEST_FILE))
            {
                $paths[] = realpath($path) ?: $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, PluginManifest>  $manifests
     */
    private function addManifest(array &$manifests, string $path, bool $is_composer_package): void
    {
        try
        {
            $data = json_decode(
                (string) file_get_contents($path.'/'.self::MANIFEST_FILE),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            $manifest = PluginManifest::fromArray($data, $path, $is_composer_package);
        }
        catch (Throwable $e)
        {
            Log::warning("Skipping plugin at [{$path}]: {$e->getMessage()}");

            return;
        }

        if (isset($manifests[$manifest->slug]))
        {
            Log::warning(
                "Skipping plugin at [{$path}]: slug [{$manifest->slug}] is already taken."
            );

            return;
        }

        $manifests[$manifest->slug] = $manifest;
    }
}
