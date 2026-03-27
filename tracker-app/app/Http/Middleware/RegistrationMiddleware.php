<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\OauthProvider;
use App\Facades\TroopTracker;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to protect registration flow routes.
 *
 * This middleware ensures that troopers can only access registration completion
 * routes if they have a valid, non-expired registration authentication session.
 * Redirects to the signup page if the session is missing or expired.
 */
class RegistrationMiddleware
{
    /**
     * Handle an incoming request and verify registration session validity.
     *
     * Checks for the presence and expiration of the registration_auth session data.
     * Redirects to signup with an error message if the session is invalid or expired.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @return Response|RedirectResponse The response from the next middleware or a redirect to signup
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
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

        $troop_tracker = app(TroopTracker::class);

        if ($troop_tracker->isXenforoOAuthRequired() && ($registration_auth['method'] ?? null) !== OauthProvider::XENFORO->value)
        {
            Session::forget('registration_auth');

            return redirect()
                ->route('auth.signup')
                ->with('error', 'Please sign up with XenForo.');
        }

        return $next($request);
    }
}
