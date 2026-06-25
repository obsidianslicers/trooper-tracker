<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Trooper;
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
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @return Response The HTTP response from the next middleware
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check())
        {
            $trooper = Auth::user();

            if (!$trooper instanceof Trooper)
            {
                return $next($request);
            }

            // Only update if it's been more than 3 minutes (optional optimization)
            if ($trooper->last_active_at === null || now()->diffInMinutes($trooper->last_active_at, true) > 3)
            {
                // Using updateQuietly to avoid triggering global
                // model events or updating 'updated_at'
                $trooper->updateQuietly([
                    Trooper::LAST_ACTIVE_AT => now(),
                ]);
            }
        }

        return $next($request);
    }
}
