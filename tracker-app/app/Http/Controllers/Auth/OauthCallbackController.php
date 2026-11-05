<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\LoginProvider;
use App\Enums\FlashType;
use App\Http\Controllers\MagicBusController;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Plugins\PluginLoginProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * Handles OAuth provider callbacks during authentication.
 *
 * This controller processes the callback from login providers (Google, or one a plugin adds)
 * after a trooper authorizes the application. It handles:
 * - Logging in existing troopers with linked OAuth accounts
 * - Redirecting inactive troopers to the inactive page
 * - Initiating registration flow for new troopers
 * - Linking OAuth providers to existing trooper accounts
 */
class OauthCallbackController extends MagicBusController
{
    private PluginLoginProviders $providers;

    protected function initialized(): void
    {
        $this->providers = app(PluginLoginProviders::class);
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
     * @param  string  $provider  The login provider's key, such as google
     * @return RedirectResponse A redirect to the appropriate destination
     */
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $refusal = $this->providers->unavailableReason($provider);

        if ($refusal !== null)
        {
            FlashType::warning($refusal);

            return redirect()->route('auth.login');
        }

        $login_provider = $this->providers->find($provider);

        if ($request->has('error'))
        {
            FlashType::warning('Login was cancelled or access was denied.');

            return redirect()->route('auth.login');
        }

        try
        {
            $provider_user = Socialite::driver($provider)->user();
        }
        catch (InvalidStateException)
        {
            FlashType::warning('Your login session expired. Please try again.');

            return redirect()->route('auth.login');
        }

        $refusal = $login_provider->refusal($provider_user);

        if ($refusal !== null)
        {
            FlashType::danger($refusal);

            return redirect()->route('auth.login');
        }

        // Find existing social account
        $account = OauthLogin::where(OauthLogin::PROVIDER, $provider)
            ->where(OauthLogin::PROVIDER_ID, $provider_user->getId())
            ->first();

        if ($account)
        {
            $trooper = $account->trooper;

            if ($trooper->is_denied)
            {
                Auth::login($trooper, true);

                return redirect()->route('account.denied');
            }

            if (!$trooper->membership_status?->canLogIn())
            {
                return redirect()->route('auth.inactive');
            }

            $login_provider->syncTrooper($trooper, $provider_user);

            Auth::login($trooper, true);

            return redirect()->intended('/');
        }

        $email = $provider_user->getEmail();

        if (empty($email))
        {
            return $this->missingEmail($login_provider);
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

        if ($trooper->is_denied)
        {
            Auth::login($trooper, true);

            return redirect()->route('account.denied');
        }

        if (!$trooper->membership_status?->canLogIn())
        {
            return redirect()->route('auth.inactive');
        }

        $login_provider->syncTrooper($trooper, $provider_user);

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

    private function missingEmail(LoginProvider $login_provider): RedirectResponse
    {
        $message = "Your {$login_provider->name()} account did not provide an email address. "
            .'Please contact an administrator.';

        if ($this->providers->required() === null)
        {
            $message .= ' Or log in with email/password.';
        }

        return redirect()
            ->route($login_provider->missingEmailRoute())
            ->withErrors(['oauth' => $message]);
    }
}
