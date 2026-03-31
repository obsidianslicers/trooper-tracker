<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Forums\XenforoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check whether the authenticated trooper's XenForo account is banned.
 *
 * The result is cached per-user so the XenForo API is only called once
 * every 15 minutes rather than on every page request.
 */
class CheckXenforoBanMiddleware
{
    /**
     * How long (in minutes) a clean (not-banned) check is cached.
     */
    private const CHECK_TTL_MINUTES = 5;

    public function __construct(private readonly XenforoService $xenforo) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('tracker.auth.require_xenforo', false))
        {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user)
        {
            return $next($request);
        }

        // Don't check on auth/logout routes — avoids redirect loops.
        if ($request->routeIs('auth.*'))
        {
            return $next($request);
        }

        // Skip the API call if a recent clean check is cached.
        $cache_key = "tracker:xenforo:ban-check:{$user->id}";

        if (Cache::get($cache_key) === 'clear')
        {
            return $next($request);
        }

        $xenforo_user_id = $this->xenforo->resolve_user_id_for_trooper($user->id);

        if ($xenforo_user_id === null)
        {
            return $next($request);
        }

        $result = $this->xenforo->get_user($xenforo_user_id);

        if ($result['status'] !== 200 || !isset($result['body']['user']))
        {
            // API unreachable — fail open, don't cache so we retry next request.
            return $next($request);
        }

        $is_banned = !empty($result['body']['user']['is_banned']);

        if ($is_banned)
        {
            Log::info('Banned XenForo user force-logged out', [
                'trooper_id' => $user->id,
                'xenforo_user_id' => $xenforo_user_id,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.login')
                ->withErrors(['banned' => 'You are currently banned. Please refer to command staff for additional information.']);
        }

        // Cache the clean result — no API call needed for the next 15 minutes.
        Cache::put($cache_key, 'clear', now()->addMinutes(self::CHECK_TTL_MINUTES));

        return $next($request);
    }
}
