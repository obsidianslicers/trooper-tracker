<?php

declare(strict_types=1);

namespace App\Messages\App\Queries;

use App\Enums\OauthProvider;
use BackedEnum;
use App\Facades\TroopTracker;
use App\Messages\MessageHandler;
use Illuminate\Support\Facades\File;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 */
final class GetConfigHandler extends MessageHandler
{
    /**
     * Constructs a GetConfig query message.
     *
     * @param TroopTracker $tracker Application facade for configuration querying
     */
    public function __construct(
        public readonly TroopTracker $tracker)
    {
        //
    }

    /**
     * Retrieves application configuration as a nested associative array.
     *
     * Returns a configuration structure containing:
     * - `meta`: Application metadata (`env`, `name`)
     * - `auth`: Authentication provider configuration with nested providers:
     *   - `xenforo`: Keys `required`, `enabled`, `configured` (bool), `url` (string if configured)
     *   - `google`: Keys `enabled`, `configured` (bool), `url` (string if configured)
     *   - `email_password`: Key `enabled` (bool)
     * - `features`: Feature toggle flags (currently empty)
     * - `localization`: Localization configuration (currently empty)
     *
     * @param GetConfig $message The GetConfig query message instance
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(object $message): array
    {
        $data = [
            'xenforo' => [
                'required' => $this->tracker->isXenforoOAuthRequired(),
                'enabled' => $this->tracker->isXenforoOAuthConfigured(),
                'configured' => $this->tracker->isXenforoOAuthConfigured(),
                'url' => $this->tracker->isXenforoOAuthConfigured()
                    ? route('auth.oauth-redirect', ['provider' => OauthProvider::XENFORO->value])
                    : null,
            ],
            'google' => [
                'enabled' => $this->tracker->isGoogleOAuthEnabled(),
                'configured' => $this->tracker->isGoogleOAuthConfigured(),
                'url' => $this->tracker->isGoogleOAuthConfigured()
                    ? route('auth.oauth-redirect', ['provider' => OauthProvider::GOOGLE->value])
                    : null,
            ],
            'email_password' => [
                'enabled' => $this->tracker->isEmailPasswordAuthEnabled(),
            ],
        ];

        return $data;
    }
}