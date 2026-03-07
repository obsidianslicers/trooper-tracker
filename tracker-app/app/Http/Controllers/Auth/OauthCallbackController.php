<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\TroopTracker;
use App\Http\Controllers\MagicBusController;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

/**
 * Handles OAuth provider callbacks during authentication.
 *
 * This controller processes the callback from OAuth providers (Google, XenForo, etc.)
 * after a trooper authorizes the application. It handles:
 * - Logging in existing troopers with linked OAuth accounts
 * - Redirecting inactive troopers to the inactive page
 * - Initiating registration flow for new troopers
 * - Linking OAuth providers to existing trooper accounts
 */
class OauthCallbackController extends MagicBusController
{
    private TroopTracker $troop_tracker;

    protected function initialize(): void
    {
        $this->troop_tracker = app(TroopTracker::class);
    }

    /**
     * Handle the incoming OAuth callback request.
     *
     * This method processes the OAuth provider's callback and handles several scenarios:
     * 1. Existing OAuth account -> Log in the trooper
     * 2. Inactive trooper -> Redirect to inactive page
     * 3. New trooper (no account) -> Store OAuth data and redirect to registration
     * 4. Existing trooper (by email) without OAuth link -> Link the provider and log in
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  string  $provider  The OAuth provider name (google, xenforo, etc.)
     * @return RedirectResponse A redirect to the appropriate destination
     */
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        if ($this->troop_tracker->isXenforoOAuthRequired() && $provider !== 'xenforo')
        {
            $this->flash->warning('Troop Tracker is configured to use XenForo for login.');

            return redirect()->route('auth.login');
        }

        $provider_user = Socialite::driver($provider)->user();

        // Find existing social account
        $account = OauthLogin::where(OauthLogin::PROVIDER, $provider)
            ->where(OauthLogin::PROVIDER_ID, $provider_user->getId())
            ->first();

        if ($account)
        {
            if (!$account->trooper->is_active)
            {
                return redirect()->route('auth.inactive');
            }

            Auth::login($account->trooper);

            return redirect()->intended('/');
        }

        $email = $provider_user->getEmail();

        if (empty($email))
        {
            $message = 'Your XenForo account did not provide an email address. Please contact an administrator.';

            if (!$this->troop_tracker->isXenforoOAuthRequired())
            {
                $message .= ' Or log in with email/password.';
            }

            return redirect()
                ->route('account.xenforo.required')
                ->withErrors([
                    'oauth' => $message,
                ]);
        }

        $trooper = Trooper::byEmail($email)->first();

        if ($trooper === null)
        {
            $registration_auth = [
                'method' => $provider,
                'provider_id' => $provider_user->getId(),
                'email' => $provider_user->getEmail(),
                'expires_at' => now()->addMinutes(value: 20),
            ];

            $oauth_pending = [
                'provider' => $provider,
                'provider_id' => $provider_user->getId(),
                'email' => $provider_user->getEmail(),
                'name' => $provider_user->getName(),
                'token' => $provider_user->token ?? null,
                'refresh_token' => $provider_user->refreshToken ?? null,
            ];

            // Session::put(self::FLASH_KEY, $messages);
            Session::put('registration_auth', $registration_auth);
            Session::put('oauth_pending', $oauth_pending);

            return redirect()->route('auth.register');
        }

        if (!$trooper->is_active)
        {
            return redirect()->route('auth.inactive');
        }

        // Link provider to existing trooper account
        OauthLogin::create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => $provider,
            OauthLogin::PROVIDER_ID => $provider_user->getId(),
            OauthLogin::TOKEN => $provider_user->token ?? null,
            OauthLogin::REFRESH_TOKEN => $provider_user->refreshToken ?? null,
        ]);

        Auth::login($trooper);

        return redirect()->intended('/');
    }
}
