<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Plugins\PluginDeployPackages;
use App\Plugins\PluginDiscovery;
use App\Plugins\PluginException;
use App\Plugins\PluginInstaller;
use App\Plugins\PluginInstallException;
use App\Plugins\PluginManager;
use App\Plugins\PluginManifest;
use App\Plugins\PluginRequirements;
use Hyperdrive\Message;

/**
 * Brings every plugin to the state an administrator chose, after a deploy has
 * installed packages and run the app's migrations. Safe to run again: a plugin
 * already in its chosen state is left alone.
 *
 * Runs pending plugin migrations, turns on plugins that were waiting for their
 * packages, records what each plugin ended up as (including errors
 * from the deploy's Composer step), and saves the packages the next deploy needs.
 *
 * @method static array call()
 */
final class ReconcilePlugins extends Message
{
    private const MISSING = 'Its files are gone from the server. Its settings and data are kept.';

    /** @var array<string, string> why a waiting plugin could not be turned on */
    private array $enable_errors = [];

    /** @var array<string, string> uploaded updates still waiting for packages */
    private array $waiting_updates = [];

    /** @var array<string, string> why an update was not applied */
    private array $upgrade_errors = [];

    /**
     * @return array<string, string> a line per plugin or package that changed or failed
     */
    public function handle(
        PluginManager $plugins,
        PluginDeployPackages $packages,
        PluginDiscovery $discovery,
        PluginInstaller $installer,
    ): array {
        $report = [];
        $composer_error = $this->composerError();

        foreach ($plugins->all() as $manifest)
        {
            $staged = $discovery->staged($manifest->slug);

            if ($staged !== null)
            {
                $this->applyStaged($installer, $manifest, $staged, $report);
            }
        }

        $this->upgradeAndEnable($plugins, $packages, $report);
        $this->recordPlugins($plugins, $discovery, $composer_error, $report);

        if ($packages->writeRequirements())
        {
            $report['packages'] = 'Plugins need different packages; the deploy installs them next.';
        }

        return $report;
    }

    /**
     * Apply an uploaded update that was waiting for its Composer packages, once
     * the deploy has installed them. If it fails, the installed version is put
     * back and keeps running.
     *
     * @param  array<string, string>  $report
     */
    private function applyStaged(
        PluginInstaller $installer,
        PluginManifest $installed,
        PluginManifest $staged,
        array &$report,
    ): void {
        $slug = $installed->slug;
        $requires = $staged->composerJson()['require'] ?? [];
        $unmet = (new PluginRequirements)->unmet([
            ...(is_array($requires) ? $requires : []),
            ...$staged->requires,
        ]);

        if ($unmet !== [])
        {
            $this->waiting_updates[$slug] = "Version {$staged->version} is uploaded and waits "
                .'for '.implode('; ', $unmet).". {$installed->version} keeps running.";

            return;
        }

        try
        {
            $installer->applyUpgrade($installer->promoteStaged($slug), $installed->version);
            $report[$slug] = "updated to {$staged->version}";
        }
        catch (PluginException|PluginInstallException $e)
        {
            $this->upgrade_errors[$slug] = $e->getMessage();
            $report[$slug] = $e->getMessage();
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

                if ($plugins->hasPendingUpgrade($manifest->slug))
                {
                    $this->upgrade_errors[$manifest->slug] = $report[$manifest->slug];
                }
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
        $problem ??= $this->upgradeProblem($plugins, $manifest);

        return match (true)
        {
            $is_enabled && $problem === null => [
                PluginStatus::ACTIVE,
                $this->waiting_updates[$manifest->slug] ?? null,
            ],
            $is_enabled => [PluginStatus::FAILED, $problem],
            $plugins->isEnablePending($manifest->slug) => [
                PluginStatus::PENDING,
                $problem ?? $this->enable_errors[$manifest->slug] ?? null,
            ],
            default => [PluginStatus::DISABLED, null],
        };
    }

    /**
     * An enabled plugin whose new files' update didn't apply is not up to date,
     * even though it still runs on its previous database changes.
     */
    private function upgradeProblem(PluginManager $plugins, PluginManifest $manifest): ?string
    {
        $slug = $manifest->slug;
        $installed = $plugins->record($slug)?->installed_version;
        $error = $this->upgrade_errors[$slug] ?? null;

        return match (true)
        {
            $plugins->hasPendingUpgrade($slug) => "The update from {$installed} to "
                ."{$manifest->version} did not finish: ".($error ?? 'it has not run yet.'),
            $error !== null => $error,
            default => null,
        };
    }

    private function recordSource(
        PluginManager $plugins,
        PluginDiscovery $discovery,
        PluginManifest $manifest,
        Plugin $record,
    ): void {
        $source = $discovery->sourceOf($manifest, $record->source);

        if ($record->source !== $source->value)
        {
            $plugins->recordSource($manifest->slug, $source);
        }
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
