<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects denied troopers to the denial appeal page when they try to access account routes.
 */
class RedirectDeniedTrooperMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->is_denied)
        {
            return redirect()->route('account.denied');
        }

        return $next($request);
    }
}
