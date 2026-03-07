<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Displays the login page for unauthenticated troopers.
 *
 * This controller handles the login page request, automatically redirecting
 * already authenticated troopers to the home page to prevent redundant logins.
 */
class LoginController extends MagicBusController
{
    /**
     * Handle the incoming request to display the login view.
     *
     * If the trooper is already authenticated, redirects them to the home page.
     * Otherwise, displays the login form with available authentication methods.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View|RedirectResponse The login page view or redirect to home if authenticated
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (Auth::check())
        {
            return redirect()->route('home');
        }

        $require_xenforo = (bool) config('tracker.auth.require_xenforo');

        $xenforo_oauth_configured = !empty(config('services.xenforo.client_id'))
            && !empty(config('services.xenforo.client_secret'))
            && !empty(config('services.xenforo.redirect'));

        $google_oauth_configured = !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'))
            && !empty(config('services.google.redirect'));

        $show_email_password_login = !$require_xenforo;
        $show_xenforo_login = $xenforo_oauth_configured;
        $show_google_login = !$require_xenforo && $google_oauth_configured;

        return view('pages.auth.login', [
            'requireXenforo' => $require_xenforo,
            'xenforoOauthConfigured' => $xenforo_oauth_configured,
            'googleOauthConfigured' => $google_oauth_configured,
            'showEmailPasswordLogin' => $show_email_password_login,
            'showXenforoLogin' => $show_xenforo_login,
            'showGoogleLogin' => $show_google_login,
        ]);
    }
}
