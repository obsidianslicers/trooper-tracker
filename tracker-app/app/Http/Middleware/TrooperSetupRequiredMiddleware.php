<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure authenticated troopers have completed initial setup.
 *
 * This middleware redirects troopers who haven't completed their account setup
 * to the setup page, except for whitelisted routes (setup, logout, pickers, verification).
 * Allows access to all routes once setup is completed.
 */
class TrooperSetupRequiredMiddleware
{
    /**
     * Handle an incoming request and verify trooper setup completion.
     *
     * For authenticated troopers who haven't completed setup (setup_completed_at is null),
     * redirects to the account setup page unless they're already on the setup page,
     * logout route, or picker routes.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @return Response The HTTP response from the next middleware or a redirect to setup
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check())
        {
            $user = Auth::user();

            if ($user->setup_completed_at === null)
            {
                if ($request->routeIs('account.setup', 'account.setup-submit', 'auth.*', 'pickers.*', 'verification.*'))
                {
                    return $next($request);
                }

                return redirect()->route('account.setup');
            }
        }

        return $next($request);
    }
}
