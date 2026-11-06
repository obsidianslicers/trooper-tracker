<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Trooper;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * A way to sign in through another site, such as Google or a forum.
 *
 * Core offers Google. A plugin adds its own with addLoginProvider() and
 * registers a Socialite driver named after key().
 */
interface LoginProvider
{
    /**
     * The Socialite driver name, also stored as oauth_logins.provider.
     * Lowercase letters, digits, dashes and underscores.
     */
    public function key(): string;

    /**
     * Shown to troopers, as in "Login with {name}".
     */
    public function name(): string;

    public function iconUrl(): string;

    /**
     * Whether the credentials it needs are set.
     */
    public function isConfigured(): bool;

    /**
     * Whether troopers must sign in this way. While a configured provider is
     * required, email/password and every other provider are turned off.
     */
    public function isRequired(): bool;

    /**
     * Why this person may not sign in, such as a ban, or null to let them in.
     */
    public function refusal(SocialiteUser $user): ?string;

    /**
     * Update the trooper from the provider's account after they sign in.
     */
    public function syncTrooper(Trooper $trooper, SocialiteUser $user): void;

    /**
     * Where to send someone whose account has no email address.
     */
    public function missingEmailRoute(): string;
}
