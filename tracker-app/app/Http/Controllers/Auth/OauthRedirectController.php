<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

/**
 * Handles OAuth provider redirect initiation.
 *
 * This controller redirects troopers to the OAuth provider's
 * authorization page where they can grant permission for the
 * application to access their account information.
 */
class OauthRedirectController extends Controller
{
    /**
     * Handle the incoming request to initiate OAuth authorization.
     *
     * Redirects the trooper to the OAuth provider's authorization page.
     * After authorization, the provider will redirect back to the callback URL.
     *
     * @param Request $request The incoming HTTP request.
     * @param string $provider The OAuth provider name (google, xenforo, etc.).
     * @return RedirectResponse A redirect to the OAuth provider's authorization page.
     */
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }
}