<?php

declare(strict_types=1);

namespace App\Facades;

use App\Contracts\LoginProvider;
use App\Plugins\PluginLoginProviders;

/**
 * TroopTracker application facade for configuration-based feature toggles.
 *
 * Provides centralized methods to check authentication provider availability
 * and configuration status across the application.
 */
class TroopTracker
{
    /**
     * The configured login provider troopers must sign in with, if any. While
     * one is required, email/password and the other providers are turned off.
     */
    public function requiredLoginProvider(): ?LoginProvider
    {
        return app(PluginLoginProviders::class)->required();
    }

    /**
     * Determine if XenForo integration is properly configured.
     *
     * Checks that both the XenForo base URL and API key are present
     * in the application configuration.
     *
     * @return bool True if XenForo integration is configured, false otherwise
     */
    public function isXenforoIntegrationConfigured(): bool
    {
        $base_url = (string) config('services.xenforo.base_url', '');
        $api_key = config('services.xenforo.api_key');

        return !empty($base_url) && !empty($api_key);
    }

    /**
     * Determine if Google Calendar sync is properly configured.
     *
     * Checks if the google-credentials.json file exists and is readable
     * at the application base path.
     *
     * @return bool True if Google sync credentials file is available, false otherwise
     */
    public function isGoogleSyncConfigured(): bool
    {
        $is_google_sync_configured = is_readable(base_path('google-credentials.json'));

        return $is_google_sync_configured;
    }

    /**
     * Determine if email/password authentication can be used.
     *
     * Email/password authentication is available unless a login provider
     * is required.
     *
     * @return bool True if email/password authentication can be used, false otherwise
     */
    public function isEmailPasswordAuthEnabled(): bool
    {
        return $this->requiredLoginProvider() === null;
    }

    /**
     * Determine if the monthly support goal widget should be shown.
     *
     * Garrisons that don't take donations through the tracker leave
     * TRACKER_SUPPORT_GOAL empty (or zero) to hide it.
     *
     * @return bool True if a positive support goal is configured, false otherwise
     */
    public function isSupportGoalEnabled(): bool
    {
        return (float) config('tracker.support.goal', 0) > 0;
    }
}
