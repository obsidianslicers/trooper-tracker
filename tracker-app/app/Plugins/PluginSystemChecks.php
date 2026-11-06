<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\SystemCheckStatus;
use App\Services\SystemCheckResult;
use Closure;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Rows plugins add to the Admin > System Check page, under "Plugins".
 */
class PluginSystemChecks
{
    /**
     * @var array<int, array{label: string, check: Closure}>
     */
    private array $checks = [];

    public function __construct(private readonly PluginManager $plugins) {}

    /**
     * @param  Closure(): (bool|SystemCheckResult)  $check  true passes, false warns.
     */
    public function add(string $label, Closure $check): void
    {
        $this->checks[] = compact('label', 'check');
    }

    /**
     * @return Collection<int, SystemCheckResult>
     */
    public function run(): Collection
    {
        $loaded = collect($this->plugins->enabled())->map($this->loadedCheck(...));

        return $loaded->values()->merge(array_map($this->runCheck(...), $this->checks));
    }

    private function loadedCheck(PluginManifest $manifest): SystemCheckResult
    {
        $is_loaded = $this->plugins->isLoaded($manifest->slug);

        return new SystemCheckResult(
            "{$manifest->name} plugin loaded",
            $is_loaded ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $is_loaded ? null : 'Enabled but failed to load. Check the logs.',
        );
    }

    private function runCheck(array $check): SystemCheckResult
    {
        try
        {
            $result = ($check['check'])();
        }
        catch (Throwable $e)
        {
            return new SystemCheckResult(
                $check['label'],
                SystemCheckStatus::FAIL,
                $e->getMessage(),
            );
        }

        if ($result instanceof SystemCheckResult)
        {
            return $result;
        }

        $status = $result ? SystemCheckStatus::PASS : SystemCheckStatus::WARN;

        return new SystemCheckResult($check['label'], $status);
    }
}
