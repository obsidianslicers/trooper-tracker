<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to intercept visitor troopers whose 6-month access window has elapsed.
 *
 * When a visitor's visitor_expires_at is in the past and they are still ACTIVE,
 * they are redirected to the visitor renewal page to explicitly request continued access.
 * Routes related to renewal, auth, and verification are exempt to prevent redirect loops.
 */
class CheckVisitorAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check())
        {
            $user = Auth::user();

            if ($user->is_visitor
                && $user->membership_status === MembershipStatus::ACTIVE
                && $user->visitor_expires_at !== null
                && $user->visitor_expires_at->isPast())
            {
                if ($request->routeIs('account.visitor-renew', 'account.visitor-renew-submit', 'auth.*', 'verification.*'))
                {
                    return $next($request);
                }

                return redirect()->route('account.visitor-renew');
            }
        }

        return $next($request);
    }
}
