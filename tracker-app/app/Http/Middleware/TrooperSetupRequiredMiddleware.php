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
    public const ALLOWED = 'allowed_during_setup';

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
                if ($user->is_denied)
                {
                    if (!$this->isAllowedDuringSetup($request, 'account.denied', 'account.denied.resubmit'))
                    {
                        return redirect()->route('account.denied');
                    }
                }
                elseif (!$this->isAllowedDuringSetup($request, 'account.setup', 'account.setup-submit'))
                {
                    return redirect()->route('account.setup');
                }
            }
        }

        return $next($request);
    }

    /**
     * A plugin opens one of its routes to troopers who haven't finished setup
     * with ->defaults(self::ALLOWED, true), such as a page to link a forum account.
     */
    private function isAllowedDuringSetup(Request $request, string ...$routes): bool
    {
        if ($request->routeIs(...$routes, ...['auth.*', 'pickers.*', 'verification.*']))
        {
            return true;
        }

        return (bool) ($request->route()?->defaults[self::ALLOWED] ?? false);
    }
}
