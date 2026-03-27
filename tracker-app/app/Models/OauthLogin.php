<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\OauthLogin as BaseOauthLogin;

/**
 * Represents an OAuth authentication login provider linked to a trooper.
 *
 * This model tracks OAuth provider connections (Google, XenForo) for troopers,
 * enabling social login functionality. Multiple OAuth providers can be linked
 * to a single trooper account.
 */
class OauthLogin extends BaseOauthLogin
{
    use HasFactory;

    /**
     * The Xenforo forum OAuth provider identifier.
     * Used when querying by provider to avoid raw string literals.
     */
    const PROVIDER_XENFORO = 'xenforo';
}
