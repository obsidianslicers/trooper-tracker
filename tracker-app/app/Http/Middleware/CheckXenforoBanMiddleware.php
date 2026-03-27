<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Forums\XenforoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check whether the authenticated trooper's XenForo account is banned
 * on every request. If it is, log them out immediately.
 */
class CheckXenforoBanMiddleware
{
    public function __construct(private readonly XenforoService $xenforo) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce when XenForo OAuth is required site-wide.
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

        $xenforo_user_id = $this->xenforo->resolve_user_id_for_trooper($user->id);

        if ($xenforo_user_id === null)
        {
            return $next($request);
        }

        $result = $this->xenforo->get_user($xenforo_user_id);

        if ($result['status'] !== 200 || !isset($result['body']['user']))
        {
            // If the API is unreachable, don't block the user — fail open.
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

        return $next($request);
    }
}
