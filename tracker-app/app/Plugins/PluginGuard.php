<?php

declare(strict_types=1);

namespace App\Plugins;

use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs plugin code where a failure should cost only that plugin's part of the
 * page or job, not the whole request: the error is logged and reported, and a
 * fallback is used instead.
 *
 * Code whose failure must stop the work, such as a form's save callback inside
 * a transaction, is not run through here.
 */
final class PluginGuard
{
    /**
     * @template T
     *
     * @param  string  $what  what the plugin was doing, for the log
     * @param  Closure(): T  $callback
     * @param  T  $fallback
     * @return T
     */
    public static function attempt(string $what, Closure $callback, mixed $fallback = null): mixed
    {
        try
        {
            return $callback();
        }
        catch (Throwable $e)
        {
            self::failed($what, $e);

            return $fallback;
        }
    }

    public static function failed(string $what, Throwable $e): void
    {
        Log::error("Plugin code failed to {$what}: {$e->getMessage()}", ['exception' => $e]);

        report($e);
    }
}
