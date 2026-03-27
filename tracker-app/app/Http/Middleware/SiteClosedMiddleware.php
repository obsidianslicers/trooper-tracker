<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MembershipRole;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to the site when it has been closed by command staff.
 *
 * Moderators and administrators can still access the site while it is closed.
 * All other users (including unauthenticated guests) are shown the closed page.
 */
class SiteClosedMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow auth/logout routes through so users are never locked out.
        if ($request->routeIs('auth.*'))
        {
            return $next($request);
        }

        if (!(bool) SiteSetting::getValue('site_closed', false))
        {
            return $next($request);
        }

        $user = Auth::user();

        $is_staff = $user && in_array($user->membership_role, [
            MembershipRole::MODERATOR,
            MembershipRole::ADMINISTRATOR,
        ], true);

        if ($is_staff)
        {
            return $next($request);
        }

        $message = (string) SiteSetting::getValue('site_message', 'Troop Tracker is currently unavailable. Please check back later.');

        return response()->view('pages.closed', compact('message'), 503);
    }
}
