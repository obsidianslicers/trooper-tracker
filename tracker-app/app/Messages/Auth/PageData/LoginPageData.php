<?php

declare(strict_types=1);

namespace App\Messages\Auth\PageData;

use App\Messages\Auth\Queries\GetAuthConfig;
use App\Messages\Auth\Resources\LoginOptionsResource;
use Hyperdrive\Message;

/**
 * The login page (auth/Login): the login providers to offer, the one that is required if any,
 * and whether email/password login is on.
 *
 * @method static array<string, mixed> call()
 */
final class LoginPageData extends Message
{
    /**
     * @return array{oauth: LoginOptionsResource}
     */
    public function handle(): array
    {
        return [
            'oauth' => new LoginOptionsResource(GetAuthConfig::call()),
        ];
    }
}
