<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the sign-up method selection page.
 *
 * This controller renders the initial sign-up page where troopers can
 * choose between different registration methods (Email, Google OAuth,
 * XenForo OAuth) before proceeding to the registration form.
 */
class SignUpController extends MagicBusController
{
    /**
     * Handle the incoming request to display the sign-up method selection page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered sign-up page view showing registration method options
     */
    public function __invoke(Request $request): View
    {
        $require_xenforo = (bool) config('tracker.auth.require_xenforo');

        $xenforo_oauth_configured = !empty(config('services.xenforo.client_id'))
            && !empty(config('services.xenforo.client_secret'))
            && !empty(config('services.xenforo.redirect'));

        $google_oauth_configured = !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'))
            && !empty(config('services.google.redirect'));

        $show_email_signup = !$require_xenforo;
        $show_xenforo_signup = $xenforo_oauth_configured;
        $show_google_signup = !$require_xenforo && $google_oauth_configured;

        return view('pages.auth.signup', [
            'requireXenforo' => $require_xenforo,
            'xenforoOauthConfigured' => $xenforo_oauth_configured,
            'googleOauthConfigured' => $google_oauth_configured,
            'showEmailSignup' => $show_email_signup,
            'showXenforoSignup' => $show_xenforo_signup,
            'showGoogleSignup' => $show_google_signup,
        ]);
    }
}
