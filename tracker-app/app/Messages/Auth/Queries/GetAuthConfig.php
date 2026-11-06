<?php

declare(strict_types=1);

namespace App\Messages\Auth\Queries;

use App\Contracts\LoginProvider;
use App\Facades\TroopTracker;
use App\Plugins\PluginLoginProviders;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Session;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for the login providers (Google, or one a
 * plugin adds) and email/password authentication, application metadata, and feature/localization settings.
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
    public function handle(TroopTracker $tracker, PluginLoginProviders $providers): array
    {
        $required = $providers->required();

        return [
            'session' => Session::get('registration_auth') ?? [],
            'providers' => array_map(fn (LoginProvider $provider): array => [
                'key' => $provider->key(),
                'name' => $provider->name(),
                'icon_url' => $provider->iconUrl(),
            ], $providers->available()),
            'required' => $required === null ? null : [
                'key' => $required->key(),
                'name' => $required->name(),
            ],
            'email_password' => [
                'enabled' => $tracker->isEmailPasswordAuthEnabled(),
            ],
        ];
    }
}
