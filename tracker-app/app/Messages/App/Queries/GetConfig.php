<?php

declare(strict_types=1);

namespace App\Messages\App\Queries;

use Hyperdrive\Message;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static void call()
 */
final class GetConfig extends Message
{
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
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $data = [
            'branding' => [
                'name' => config('app.name'),
            ],
            'meta' => [
                'env' => config('app.env'),
                'base_url' => config('app.url'),
            ],
        ];

        return $data;
    }
}
