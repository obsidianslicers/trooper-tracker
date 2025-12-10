<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;


class TrooperSetupRequiredMiddleware
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

            if ($user->setup_completed_at == null)
            {
                if ($request->routeIs('account.setup', 'account.setup-submit'))
                {
                    return $next($request);
                }

                return redirect()->route('account.setup');
            }
        }

        return $next($request);
    }
}
