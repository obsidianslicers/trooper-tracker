<?php

declare(strict_types=1);

namespace App\Messages\Auth\Queries;

use App\Facades\TroopTracker;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Session;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static array call()
 */
final class GetAuthConfig extends Message
{
    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(TroopTracker $tracker): array
    {
        return [
            'session' => Session::get('registration_auth') ?? [],
            'xenforo' => [
                'name' => $tracker->getXenforoOAuthName(),
                'required' => $tracker->isXenforoOAuthRequired(),
                'configured' => $tracker->isXenforoOAuthConfigured(),
            ],
            'google' => [
                'enabled' => $tracker->isGoogleOAuthEnabled(),
                'configured' => $tracker->isGoogleOAuthConfigured(),
            ],
            'email_password' => [
                'enabled' => $tracker->isEmailPasswordAuthEnabled(),
            ],
        ];
    }
}
