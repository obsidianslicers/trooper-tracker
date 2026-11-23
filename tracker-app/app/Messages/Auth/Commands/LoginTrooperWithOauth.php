<?php

declare(strict_types=1);

namespace App\Messages\Auth\Commands;

use App\Contracts\LoginProvider;
use App\Enums\OauthLoginOutcome;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Logs in the trooper a login provider vouched for.
 *
 * A trooper already linked to the provider account is logged in. One with the same email is
 * linked, then logged in. Anyone else is sent to register, with the provider's details kept
 * in the session.
 *
 * @method static OauthLoginOutcome call(LoginProvider $login_provider, SocialiteUser $provider_user)
 */
final class LoginTrooperWithOauth extends Message
{
    public function __construct(
        private readonly LoginProvider $login_provider,
        private readonly SocialiteUser $provider_user,
    ) {}

    public function handle(): OauthLoginOutcome
    {
        $account = OauthLogin::where(OauthLogin::PROVIDER, $this->login_provider->key())
            ->where(OauthLogin::PROVIDER_ID, $this->provider_user->getId())
            ->first();

        if ($account)
        {
            return $this->login($account->trooper, remember: true);
        }

        $email = $this->provider_user->getEmail();

        if (empty($email))
        {
            return OauthLoginOutcome::MISSING_EMAIL;
        }

        $trooper = Trooper::byEmail($email)->first();

        if ($trooper === null)
        {
            $this->rememberForRegistration();

            return OauthLoginOutcome::REGISTER;
        }

        return $this->login($trooper, remember: false, link: true);
    }

    private function login(Trooper $trooper, bool $remember, bool $link = false): OauthLoginOutcome
    {
        if ($trooper->is_denied)
        {
            Auth::login($trooper, true);

            return OauthLoginOutcome::DENIED;
        }

        if (!$trooper->membership_status?->canLogIn())
        {
            return OauthLoginOutcome::INACTIVE;
        }

        $this->login_provider->syncTrooper($trooper, $this->provider_user);

        if ($link)
        {
            $this->link($trooper);
        }

        Auth::login($trooper, $remember);

        return OauthLoginOutcome::LOGGED_IN;
    }

    private function link(Trooper $trooper): void
    {
        OauthLogin::create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => $this->login_provider->key(),
            OauthLogin::PROVIDER_ID => $this->provider_user->getId(),
            OauthLogin::TOKEN => $this->provider_user->token ?? null,
            OauthLogin::REFRESH_TOKEN => $this->provider_user->refreshToken ?? null,
        ]);
    }

    private function rememberForRegistration(): void
    {
        $provider = $this->login_provider->key();

        Session::put('registration_auth', [
            'method' => $provider,
            'provider_id' => $this->provider_user->getId(),
            'email' => $this->provider_user->getEmail(),
            'expires_at' => now()->addMinutes(value: 20),
        ]);

        Session::put('oauth_pending', [
            'provider' => $provider,
            'provider_id' => $this->provider_user->getId(),
            'email' => $this->provider_user->getEmail(),
            'name' => $this->provider_user->getName(),
            'token' => $this->provider_user->token ?? null,
            'refresh_token' => $this->provider_user->refreshToken ?? null,
        ]);
    }
}
