<?php

declare(strict_types=1);

namespace App\Messages\Auth\PageData;

use App\Messages\Auth\Queries\GetAuthConfig;
use Hyperdrive\Message;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static array<string, mixed> call()
 */
final class LoginPageData extends Message
{
    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $data = [
            'oauth' => GetAuthConfig::call(),
        ];

        return $data;
    }
}
