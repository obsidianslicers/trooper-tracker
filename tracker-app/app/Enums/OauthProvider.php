<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Supported OAuth identity providers.
 *
 * Represents external authentication sources that troopers can use to sign in.
 */
enum OauthProvider: string
{
    use HasEnumHelpers;

    /**
     * XenForo OAuth provider.
     */
    case XENFORO = 'xenforo';

    /**
     * Google OAuth provider.
     */
    case GOOGLE = 'google';
}
