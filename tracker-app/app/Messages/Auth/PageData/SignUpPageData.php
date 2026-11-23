<?php

declare(strict_types=1);

namespace App\Messages\Auth\PageData;

use App\Messages\Auth\Queries\GetAuthConfig;
use App\Messages\Auth\Resources\SignUpOptionsResource;
use Hyperdrive\Message;

/**
 * The sign-up page (auth/SignUp): the login providers to sign up with, and the one that is
 * required if any.
 *
 * @method static array<string, mixed> call()
 */
final class SignUpPageData extends Message
{
    /**
     * @return array{oauth: SignUpOptionsResource}
     */
    public function handle(): array
    {
        return [
            'oauth' => new SignUpOptionsResource(GetAuthConfig::call()),
        ];
    }
}
