<?php

declare(strict_types=1);

namespace App\Plugins;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finds plugins: each folder holding a plugin.json file, in the bundled plugins
 * folder or the persistent one (uploaded and copied plugins).
 */
class PluginDiscovery
{
    public const MANIFEST_FILE = 'plugin.json';

    public const STAGED_FOLDER = '.pending';

    public function __construct(
        private readonly string $local_path,
        private readonly ?string $uploads_path = null,
    ) {}

    public function localPath(): string
    {
        return $this->local_path;
    }

    public function uploadsPath(): ?string
    {
        return $this->uploads_path;
    }

    /**
     * Where an uploaded update waits for the packages it needs, while the
     * installed version keeps running.
     */
    public function stagedPath(string $slug): ?string
    {
        return $this->uploads_path === null
            ? null
            : rtrim($this->uploads_path, '/').'/'.self::STAGED_FOLDER.'/'.$slug;
    }

    public function staged(string $slug): ?PluginManifest
    {
        $path = $this->stagedPath($slug);

        if ($path === null || !is_file($path.'/'.self::MANIFEST_FILE))
        {
            return null;
        }

        $manifests = [];
        $this->addManifest($manifests, $path);

        return $manifests[$slug] ?? null;
    }

    public function isUploaded(PluginManifest $manifest): bool
    {
        if ($this->uploads_path === null)
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

        foreach ($this->localPaths() as $path)
        {
            $this->addManifest($manifests, $path);
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
     * @param  array<string, PluginManifest>  $manifests
     */
    private function addManifest(array &$manifests, string $path): void
    {
        try
        {
            $data = json_decode(
                (string) file_get_contents($path.'/'.self::MANIFEST_FILE),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            $manifest = PluginManifest::fromArray($data, $path);
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
