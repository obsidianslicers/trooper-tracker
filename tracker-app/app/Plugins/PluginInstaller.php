<?php

declare(strict_types=1);

namespace App\Plugins;

use Composer\Semver\Comparator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use JsonException;
use Throwable;
use ZipArchive;

/**
 * Unpacks an uploaded plugin zip into the uploads folder.
 *
 * The archive is checked before anything is written: no absolute paths, no ".."
 * segments, no symbolic links, a cap on file count and unzipped size, and a valid
 * plugin.json at the root or inside a single top folder. Files are unpacked into a
 * temporary folder and moved into place only when every file was written.
 *
 * Uploading a newer version of an uploaded plugin replaces its files and keeps
 * the old ones in uploads/.previous/<slug> until the upgrade is done, so they can
 * be put back if it fails.
 */
final class PluginInstaller
{
    private const FILE_TYPE_MASK = 0170000;

    private const SYMLINK_TYPE = 0120000;

    private const IGNORED_PREFIX = '__MACOSX/';

    private const PREVIOUS_FOLDER = '.previous';

    public function __construct(
        private readonly PluginManager $plugins,
        private readonly PluginDiscovery $discovery,
        private readonly Filesystem $files,
        private readonly PluginRequirements $requirements,
    ) {}

    /**
     * previous_version is set when the upload replaced an older uploaded version.
     *
     * @return array{manifest: PluginManifest, previous_version: ?string}
     */
    public function install(string $archive_path): array
    {
        $uploads_path = $this->uploadsPath();
        $zip = $this->open($archive_path);

        try
        {
            $entries = $this->entries($zip);
            $root = $this->root($entries);
            $manifest = $this->manifest($zip, $root, $uploads_path);
            $previous_version = $this->checkUpgrade($manifest);
            $this->checkRequirements($zip, $root);
            $this->extract($zip, $entries, $root, $manifest->path, $previous_version !== null);
        }
        finally
        {
            $zip->close();
        }

        $this->plugins->forgetManifests();

        return [
            'manifest' => $this->plugins->findOrFail($manifest->slug),
            'previous_version' => $previous_version,
        ];
    }

    /**
     * Put back the files an upgrade replaced.
     */
    public function restorePrevious(PluginManifest $manifest): void
    {
        $previous = $this->previousPath($manifest);

        if ($this->files->isDirectory($previous))
        {
            $this->files->deleteDirectory($manifest->path);
            $this->files->moveDirectory($previous, $manifest->path);
        }

        $this->plugins->forgetManifests();
    }

    /**
     * Remove the files an upgrade replaced, once the upgrade worked.
     */
    public function forgetPrevious(PluginManifest $manifest): void
    {
        $this->files->deleteDirectory($this->previousPath($manifest));
    }

    /**
     * @return string|null The installed version this upload replaces, or null for
     *                     a new plugin.
     */
    private function checkUpgrade(PluginManifest $manifest): ?string
    {
        $installed = $this->plugins->find($manifest->slug);

        if ($installed === null)
        {
            if ($this->files->exists($manifest->path))
            {
                throw new PluginInstallException(
                    "The folder for [{$manifest->slug}] already exists. Remove it first."
                );
            }

            return null;
        }

        if (!$this->discovery->isUploaded($installed))
        {
            throw new PluginInstallException(
                "[{$manifest->slug}] came with a deploy or Composer. Update it the same way."
            );
        }

        if (!Comparator::greaterThan($manifest->version, $installed->version))
        {
            throw new PluginInstallException(
                "Version {$manifest->version} is not newer than the installed "
                ."{$installed->version}."
            );
        }

        return $installed->version;
    }

    private function previousPath(PluginManifest $manifest): string
    {
        return dirname($manifest->path).'/'.self::PREVIOUS_FOLDER.'/'.$manifest->slug;
    }

    private function uploadsPath(): string
    {
        $path = rtrim((string) $this->discovery->uploadsPath(), '/');

        if ($path === '')
        {
            throw new PluginInstallException('No folder is set for uploaded plugins.');
        }

        $this->files->ensureDirectoryExists($path);

        return $path;
    }

    private function open(string $archive_path): ZipArchive
    {
        $zip = new ZipArchive;

        if ($zip->open($archive_path, ZipArchive::RDONLY) !== true)
        {
            throw new PluginInstallException('The file is not a valid zip archive.');
        }

        return $zip;
    }

    /**
     * @return array<string, int> Each file's name in the archive and its unzipped size.
     */
    private function entries(ZipArchive $zip): array
    {
        if ($zip->numFiles > (int) config('plugins.uploads.max_files', 2000))
        {
            throw new PluginInstallException('The archive holds too many files.');
        }

        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index++)
        {
            $stat = $zip->statIndex($index);
            $this->checkEntry($zip, $index, (string) ($stat['name'] ?? ''));

            if (!str_ends_with($stat['name'], '/') && !$this->isIgnored($stat['name']))
            {
                $entries[$stat['name']] = (int) $stat['size'];
            }
        }

        if (array_sum($entries) > (int) config('plugins.uploads.max_unzipped_kilobytes') * 1024)
        {
            throw new PluginInstallException('The archive is too large once unzipped.');
        }

        return $entries;
    }

    private function checkEntry(ZipArchive $zip, int $index, string $name): void
    {
        $segments = explode('/', $name);
        $is_unsafe = $name === ''
            || str_contains($name, "\0")
            || str_contains($name, '\\')
            || str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:/', $name) === 1
            || in_array('..', $segments, true);

        if ($is_unsafe)
        {
            throw new PluginInstallException("The archive has an unsafe path [{$name}].");
        }

        $zip->getExternalAttributesIndex($index, $system, $attributes);

        if ($system === ZipArchive::OPSYS_UNIX
            && (($attributes >> 16) & self::FILE_TYPE_MASK) === self::SYMLINK_TYPE)
        {
            throw new PluginInstallException("The archive has a symbolic link [{$name}].");
        }
    }

    /**
     * @param  array<string, int>  $entries
     * @return string The folder inside the archive that holds plugin.json ("" for the root).
     */
    private function root(array $entries): string
    {
        if (isset($entries[PluginDiscovery::MANIFEST_FILE]))
        {
            return '';
        }

        $folders = array_unique(array_map(
            fn (string $name): string => strstr($name, '/', true) ?: '',
            array_keys($entries),
        ));

        $root = count($folders) === 1 ? reset($folders).'/' : null;

        $has_manifest = $root !== null && isset($entries[$root.PluginDiscovery::MANIFEST_FILE]);

        if (!$has_manifest || $root === '/')
        {
            throw new PluginInstallException(
                'The archive needs a plugin.json at its root or inside a single top folder.'
            );
        }

        return $root;
    }

    private function manifest(ZipArchive $zip, string $root, string $uploads_path): PluginManifest
    {
        try
        {
            $json = (string) $zip->getFromName($root.PluginDiscovery::MANIFEST_FILE);
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $slug = is_array($data) ? (string) ($data['slug'] ?? '') : '';
            $manifest = PluginManifest::fromArray((array) $data, $uploads_path.'/'.$slug, false);
        }
        catch (JsonException $e)
        {
            throw new PluginInstallException("plugin.json is not valid JSON: {$e->getMessage()}");
        }
        catch (Throwable $e)
        {
            throw new PluginInstallException("plugin.json is not valid: {$e->getMessage()}");
        }

        return $manifest;
    }

    private function checkRequirements(ZipArchive $zip, string $root): void
    {
        $json = $zip->getFromName($root.'composer.json');
        $composer = $json === false ? null : json_decode($json, true);
        $required = is_array($composer) ? (array) ($composer['require'] ?? []) : [];
        $missing = $this->requirements->missing($required);

        if ($missing !== [])
        {
            throw new PluginInstallException(
                'This plugin needs packages the app does not have: '.implode(', ', $missing)
                .'. Install it with Composer instead of uploading it.'
            );
        }
    }

    /**
     * @param  array<string, int>  $entries
     */
    private function extract(
        ZipArchive $zip,
        array $entries,
        string $root,
        string $target,
        bool $is_upgrade,
    ): void {
        $staging = dirname($target).'/.installing-'.Str::random(12);

        try
        {
            foreach ($entries as $name => $size)
            {
                $this->extractFile($zip, $name, $size, $staging.'/'.substr($name, strlen($root)));
            }

            $is_upgrade ? $this->swapIn($staging, $target) : $this->moveIn($staging, $target);
        }
        finally
        {
            $this->files->deleteDirectory($staging);
        }
    }

    private function moveIn(string $staging, string $target): void
    {
        if (!$this->files->moveDirectory($staging, $target))
        {
            throw new PluginInstallException('The plugin could not be moved into place.');
        }
    }

    private function swapIn(string $staging, string $target): void
    {
        $previous = dirname($target).'/'.self::PREVIOUS_FOLDER.'/'.basename($target);

        $this->files->deleteDirectory($previous);
        $this->files->ensureDirectoryExists(dirname($previous));

        if (!$this->files->moveDirectory($target, $previous))
        {
            throw new PluginInstallException('The installed version could not be set aside.');
        }

        if (!$this->files->moveDirectory($staging, $target))
        {
            $this->files->moveDirectory($previous, $target);

            throw new PluginInstallException('The new version could not be moved into place.');
        }
    }

    private function extractFile(
        ZipArchive $zip,
        string $name,
        int $size,
        string $destination,
    ): void {
        $this->files->ensureDirectoryExists(dirname($destination));
        $source = $zip->getStream($name);
        $output = fopen($destination, 'wb');

        if ($source === false || $output === false)
        {
            throw new PluginInstallException("Could not unpack [{$name}].");
        }

        $written = stream_copy_to_stream($source, $output, $size + 1);
        fclose($source);
        fclose($output);

        if ($written !== $size)
        {
            throw new PluginInstallException("[{$name}] did not unpack to its stated size.");
        }
    }

    private function isIgnored(string $name): bool
    {
        return str_starts_with($name, self::IGNORED_PREFIX) || basename($name) === '.DS_Store';
    }
}
