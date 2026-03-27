<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OauthProvider;
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::PROVIDER => OauthProvider::class,
        ]);
    }
}
