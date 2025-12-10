<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;


class UpdateLastActiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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
