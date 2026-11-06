<?php

declare(strict_types=1);

namespace App\Auth;

use App\Contracts\LoginProvider;
use App\Models\Trooper;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Sign in with a Google account.
 */
final class GoogleLoginProvider implements LoginProvider
{
    public const KEY = 'google';

    public function key(): string
    {
        return self::KEY;
    }

    public function name(): string
    {
        return 'Google';
    }

    public function iconUrl(): string
    {
        return 'https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function refusal(SocialiteUser $user): ?string
    {
        return null;
    }

    public function syncTrooper(Trooper $trooper, SocialiteUser $user): void {}

    public function missingEmailRoute(): string
    {
        return 'auth.login';
    }
}
