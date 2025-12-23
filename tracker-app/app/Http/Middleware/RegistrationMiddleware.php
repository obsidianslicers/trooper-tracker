<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class RegistrationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $registration_auth = Session::get('registration_auth');

        if (!$registration_auth)
        {
            return redirect()
                ->route('auth.signup')
                ->with('error', 'Please sign up again.');
        }

        if (now()->greaterThan($registration_auth['expires_at']))
        {
            Session::forget('registration_auth');

            return redirect()
                ->route('auth.signup')
                ->with('error', 'Your registration session expired.');
        }

        return $next($request);
    }
}