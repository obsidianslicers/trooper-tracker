<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Commands;

use App\Contracts\LoginProvider;
use App\Enums\FlashType;
use App\Enums\OauthLoginOutcome;
use App\Http\Controllers\PageController;
use App\Messages\Auth\Commands\LoginTrooperWithOauth;
use App\Plugins\PluginLoginProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
class HandleOauthCallbackController extends PageController
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
            FlashType::warning(__('auth.Login was cancelled or access was denied.'));

            return redirect()->route('auth.login');
        }

        try
        {
            $provider_user = Socialite::driver($provider)->user();
        }
        catch (InvalidStateException)
        {
            FlashType::warning(__('auth.Your login session expired. Please try again.'));

            return redirect()->route('auth.login');
        }

        $refusal = $login_provider->refusal($provider_user);

        if ($refusal !== null)
        {
            FlashType::danger($refusal);

            return redirect()->route('auth.login');
        }

        $outcome = LoginTrooperWithOauth::call(login_provider: $login_provider, provider_user: $provider_user);

        return match ($outcome)
        {
            OauthLoginOutcome::LOGGED_IN => redirect()->intended('/'),
            OauthLoginOutcome::DENIED => redirect()->route('account.denied'),
            OauthLoginOutcome::INACTIVE => redirect()->route('auth.inactive'),
            OauthLoginOutcome::REGISTER => redirect()->route('auth.register'),
            OauthLoginOutcome::MISSING_EMAIL => $this->missingEmail($login_provider),
        };
    }

    private function missingEmail(LoginProvider $login_provider): RedirectResponse
    {
        $message = __(
            'auth.Your :provider account did not provide an email address. Please contact an administrator.',
            ['provider' => $login_provider->name()]
        );

        if ($this->providers->required() === null)
        {
            $message .= ' '.__('auth.Or log in with email/password.');
        }

        return redirect()
            ->route($login_provider->missingEmailRoute())
            ->withErrors(['oauth' => $message]);
    }
}
