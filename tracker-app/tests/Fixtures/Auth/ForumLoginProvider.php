<?php

declare(strict_types=1);

namespace Tests\Fixtures\Auth;

use App\Contracts\LoginProvider;
use App\Models\Trooper;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * A login provider for tests; set the statics to shape it.
 */
final class ForumLoginProvider implements LoginProvider
{
    public static bool $configured = true;

    public static bool $required = false;

    public static function reset(): void
    {
        self::$configured = true;
        self::$required = false;
    }

    public function key(): string
    {
        return 'forum';
    }

    public function name(): string
    {
        return 'Florida Garrison';
    }

    public function iconUrl(): string
    {
        return 'https://example.com/forum.png';
    }

    public function isConfigured(): bool
    {
        return self::$configured;
    }

    public function isRequired(): bool
    {
        return self::$required;
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
