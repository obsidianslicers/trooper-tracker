<?php

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify that the authenticated trooper has an active membership.
 *
 * This middleware ensures the authenticated user has an active membership status.
 * Returns a 401 Unauthorized response if the user is not authenticated or does
 * not have active membership status.
 */
class CheckActiveTrooperMiddleware
{
    /**
     * Handle an incoming request and verify active trooper status.
     *
     * Checks that the authenticated user exists and has an active membership status.
     * Aborts with 401 if either condition is not met.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response from the next middleware
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 401 if user is not authenticated or not active
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->membership_status !== MembershipStatus::ACTIVE)
        {
            abort(401, 'Unauthorized.');
        }

        return $next($request);
    }
}
