<?php

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


}
