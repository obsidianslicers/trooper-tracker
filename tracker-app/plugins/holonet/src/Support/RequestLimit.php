<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Per-minute limits on the requests other trackers make here, and the "slow
 * down" answer when one is used up.
 */
final class RequestLimit
{
    /**
     * Count one request against each limit. Returns a 429 answer when any of them
     * is used up, or null to carry on.
     *
     * @param  array<string, string>  $limits  config key under plugins.holonet.limits => key
     */
    public static function check(array $limits): ?JsonResponse
    {
        foreach ($limits as $setting => $key)
        {
            $max = (int) config("plugins.holonet.limits.{$setting}");
            $key = "holonet:{$setting}:{$key}";

            if ($max > 0 && RateLimiter::tooManyAttempts($key, $max))
            {
                return self::tooMany(RateLimiter::availableIn($key));
            }
        }

        foreach ($limits as $setting => $key)
        {
            RateLimiter::hit("holonet:{$setting}:{$key}", 60);
        }

        return null;
    }

    /**
     * Count a trooper starting or allowing a link. False once they've used up
     * the hourly limit.
     */
    public static function attemptLink(int $trooper_id): bool
    {
        $max = (int) config('plugins.holonet.limits.links_per_hour');
        $key = "holonet:links_per_hour:{$trooper_id}";

        if ($max > 0 && RateLimiter::tooManyAttempts($key, $max))
        {
            return false;
        }

        RateLimiter::hit($key, 3600);

        return true;
    }

    private static function tooMany(int $retry_after): JsonResponse
    {
        return response()
            ->json(['error' => 'too_many_requests', 'retry_after' => $retry_after], 429)
            ->header('Retry-After', (string) max(1, $retry_after));
    }
}
