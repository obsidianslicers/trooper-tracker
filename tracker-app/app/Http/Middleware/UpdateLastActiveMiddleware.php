<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to track trooper activity timestamps.
 *
 * This middleware updates the last_active_at timestamp for authenticated troopers.
 * Includes a 3-minute throttle to prevent excessive database writes on every request.
 */
class UpdateLastActiveMiddleware
{
    /**
     * Handle an incoming request and update trooper activity timestamp.
     *
     * For authenticated troopers, updates the last_active_at timestamp if it's null
     * or more than 3 minutes have passed since the last update. This throttling
     * prevents excessive database writes while maintaining reasonable accuracy.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response from the next middleware
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check())
        {
            $user = Auth::user();

            // Only update if it's been more than 3 minutes (optional optimization)
            if ($user->last_active_at === null || now()->diffInMinutes($user->last_active_at) > 3)
            {
                $user->last_active_at = now();

                $user->save();
            }
        }

        return $next($request);
    }
}
