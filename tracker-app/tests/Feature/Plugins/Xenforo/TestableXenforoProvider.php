<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use Laravel\Socialite\Two\User;
use TroopTracker\Plugins\Xenforo\Socialite\XenforoProvider;

class TestableXenforoProvider extends XenforoProvider
{
    public function publicGetAuthUrl(string $state): string
    {
        return $this->getAuthUrl($state);
    }

    public function publicGetTokenUrl(): string
    {
        return $this->getTokenUrl();
    }

    /**
     * @param  array<string, mixed>  $user
     */
    public function publicMapUserToObject(array $user): User
    {
        return $this->mapUserToObject($user);
    }

    /**
     * @return array<string, string>
     */
    public function publicGetTokenFields(string $code): array
    {
        return $this->getTokenFields($code);
    }
}
