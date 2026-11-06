<?php

declare(strict_types=1);

namespace App\Messages\App\Queries;

use Hyperdrive\Message;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (Google or
 * plugin login providers, email/password authentication), application metadata, and feature/localization settings.
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
     * - `branding`: `name`
     * - `meta`: `env`, `base_url`
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
