<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What happened when a trooper came back from a login provider.
 */
enum OauthLoginOutcome: string
{
    /**
     * They are logged in.
     */
    case LOGGED_IN = 'logged_in';

    /**
     * Their membership was denied. They are logged in so they can see why and apply again.
     */
    case DENIED = 'denied';

    /**
     * Their membership doesn't allow logging in.
     */
    case INACTIVE = 'inactive';

    /**
     * No trooper has this email yet. The provider's details wait in the session for registration.
     */
    case REGISTER = 'register';

    /**
     * The provider didn't share an email address, so there is no one to match them to.
     */
    case MISSING_EMAIL = 'missing_email';
}
