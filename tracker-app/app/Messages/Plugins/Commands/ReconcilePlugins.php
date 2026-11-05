<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Models\PluginPackage;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginException;
use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use Composer\InstalledVersions;
use Hyperdrive\Message;

/**
 * Brings every plugin to the state an administrator chose, after a deploy has
 * installed packages and run the app's migrations. Safe to run again: a plugin
 * already in its chosen state is left alone.
 *
 * Runs pending plugin migrations, turns on plugins that were waiting for their
 * packages, records what each package and plugin ended up as (including errors
 * from the deploy's Composer step), and saves the packages the next deploy needs.
 *
 * @method static array call()
 */
final class ReconcilePlugins extends Message
{
    private const MISSING = 'Its files are gone from the server. Its settings and data are kept.';

    /** @var array<string, string> why a waiting plugin could not be turned on */
    private array $enable_errors = [];

    /**
     * @return array<string, string> a line per plugin or package that changed or failed
     */
    public function handle(
        PluginManager $plugins,
        PluginDeployPackages $packages,
        PluginDiscovery $discovery,
    ): array {
        $report = [];
        $composer_error = $this->composerError();

        $this->recordPackages($composer_error, $report);
        $this->upgradeAndEnable($plugins, $packages, $report);
        $this->recordPlugins($plugins, $discovery, $composer_error, $report);

        if ($packages->writeRequirements())
        {
            $report['packages'] = 'Plugins need different packages; the deploy installs them next.';
        }

        return $report;
    }

    /**
     * @param  array<string, string>  $report
     */
    private function recordPackages(?string $composer_error, array &$report): void
    {
        foreach (PluginPackage::query()->get() as $package)
        {
            $installed = InstalledVersions::isInstalled($package->name)
                ? InstalledVersions::getPrettyVersion($package->name)
                : null;
            $error = $installed === null
                ? ($composer_error ?? 'Not installed yet; the next deploy installs it.')
                : null;

            if ($package->installed_version !== $installed || $package->last_error !== $error)
            {
                $package->installed_version = $installed ?? $package->installed_version;
                $package->last_error = $error;
                $package->checked_at = now();
                $package->save();
                $report[$package->name] = $error ?? "installed {$installed}";
            }
        }
    }

    /**
     * @param  array<string, string>  $report
     */
    private function upgradeAndEnable(
        PluginManager $plugins,
        PluginDeployPackages $packages,
        array &$report,
    ): void {
        foreach ($plugins->all() as $manifest)
        {
            if ($plugins->hasPendingUpgrade($manifest->slug)
                && $plugins->unmetRequirements($manifest->slug) === [])
            {
                $report[$manifest->slug] = $this->attempt(
                    fn () => $plugins->upgrade($manifest->slug),
                    "upgraded to {$manifest->version}",
                );
            }
        }

        foreach ($packages->enablePending() as $slug => $error)
        {
            if ($error === null)
            {
                $report[$slug] = 'turned on';

                continue;
            }

            $this->enable_errors[$slug] = $error;
        }
    }

    /**
     * @param  array<string, string>  $report
     */
    private function recordPlugins(
        PluginManager $plugins,
        PluginDiscovery $discovery,
        ?string $composer_error,
        array &$report,
    ): void {
        foreach (Plugin::query()->get() as $record)
        {
            $manifest = $plugins->find($record->slug);

            [$status, $error] = $manifest === null
                ? [PluginStatus::MISSING, self::MISSING]
                : $this->status($plugins, $manifest, $composer_error);

            if ($manifest !== null)
            {
                $this->recordSource($plugins, $discovery, $manifest, $record);
            }

            $changed = $plugins->recordStatus($record->slug, $status, $error);

            if ($changed && $error !== null)
            {
                $report[$record->slug] ??= "{$status->value}: {$error}";
            }
        }
    }

    /**
     * @return array{0: PluginStatus, 1: ?string}
     */
    private function status(
        PluginManager $plugins,
        PluginManifest $manifest,
        ?string $composer_error,
    ): array {
        $unmet = $plugins->unmetRequirements($manifest->slug);
        $problem = $unmet === [] ? null : 'It needs '.implode('; ', $unmet).'.';
        $problem = $problem !== null && $composer_error !== null
            ? "{$problem} The last deploy could not install packages: {$composer_error}"
            : $problem;

        $is_enabled = $plugins->isEnabled($manifest->slug);

        return match (true)
        {
            $is_enabled && $problem === null => [PluginStatus::ACTIVE, null],
            $is_enabled => [PluginStatus::FAILED, $problem],
            $plugins->isEnablePending($manifest->slug) => [
                PluginStatus::PENDING,
                $problem ?? $this->enable_errors[$manifest->slug] ?? null,
            ],
            default => [PluginStatus::DISABLED, null],
        };
    }

    private function recordSource(
        PluginManager $plugins,
        PluginDiscovery $discovery,
        PluginManifest $manifest,
        Plugin $record,
    ): void {
        $from_composer = $manifest->is_composer_package && !$this->inFolder($manifest, $discovery);
        $source = match (true)
        {
            $record->source === PluginSource::UPLOADED->value => PluginSource::UPLOADED,
            $from_composer => PluginSource::COMPOSER,
            $this->inFolder($manifest, $discovery, uploads: true) => PluginSource::CUSTOM,
            default => PluginSource::BUNDLED,
        };

        if ($record->source !== $source->value)
        {
            $plugins->recordSource($manifest->slug, $source);
        }
    }

    private function inFolder(
        PluginManifest $manifest,
        PluginDiscovery $discovery,
        bool $uploads = false,
    ): bool {
        $folders = $uploads
            ? [$discovery->uploadsPath()]
            : [$discovery->localPath(), $discovery->uploadsPath()];
        $path = realpath($manifest->path) ?: $manifest->path;

        foreach (array_filter($folders) as $folder)
        {
            if (str_starts_with($path, (realpath($folder) ?: $folder).DIRECTORY_SEPARATOR))
            {
                return true;
            }
        }

        return false;
    }

    private function attempt(callable $callback, string $success): string
    {
        try
        {
            $callback();

            return $success;
        }
        catch (PluginException $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * What went wrong in the deploy's Composer step, as deploy.sh saved it.
     */
    private function composerError(): ?string
    {
        $file = config('plugins.deploy_path').'/composer-result.json';
        $result = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (!is_array($result) || ($result['ok'] ?? true))
        {
            return null;
        }

        return trim((string) ($result['error'] ?? 'Composer failed.')) ?: 'Composer failed.';
    }
}
