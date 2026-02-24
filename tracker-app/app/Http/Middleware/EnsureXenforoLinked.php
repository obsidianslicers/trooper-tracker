<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\OauthLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated trooper has a linked XenForo OAuth account.
 *
 * If not, redirect them to the XenForo linking page.
 */
class EnsureXenforoLinked
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Allow access to the linking routes themselves to avoid redirect loops
        if ($request->routeIs('account.xenforo.*') || $request->routeIs('auth.oauth-*')) {
            return $next($request);
        }

        $has_xenforo = OauthLogin::where(OauthLogin::TROOPER_ID, $user->id)
            ->where(OauthLogin::PROVIDER, 'xenforo')
            ->exists();

        if (! $has_xenforo) {
            return redirect()->route('account.xenforo.required');
        }

        return $next($request);
    }
}
