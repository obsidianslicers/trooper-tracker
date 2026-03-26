<?php

declare(strict_types=1);

namespace App\Facades;

/**
 * TroopTracker application facade for configuration-based feature toggles.
 *
 * Provides centralized methods to check authentication provider availability
 * and configuration status across the application.
 */
class TroopTracker
{
    /**
     * Determine if XenForo OAuth authentication is required.
     *
     * When required, email/password and Google OAuth authentication methods
     * are disabled throughout the application.
     *
     * @return bool True if XenForo OAuth is required, false otherwise
     */
    public function isXenforoOAuthRequired(): bool
    {
        $require_xenforo = (bool) config('tracker.auth.require_xenforo', false);

        return $require_xenforo && $this->isXenforoOAuthConfigured();
    }

    /**
     * Determine if XenForo OAuth is properly configured.
     *
     * Checks that all required XenForo OAuth credentials (client ID, client secret,
     * and redirect URL) are present in the application configuration.
     *
     * @return bool True if XenForo OAuth is configured, false otherwise
     */
    public function isXenforoOAuthConfigured(): bool
    {
        $xenforo_oauth_configured = !empty(config('services.xenforo.client_id'))
            && !empty(config('services.xenforo.client_secret'))
            && !empty(config('services.xenforo.redirect'));

        return $xenforo_oauth_configured;
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
        $base_url = (string) config('services.xenforo.base_url', config('xenforo.base_url', env('XENFORO_BASE_URL', '')));
        $api_key = config('services.xenforo.api_key', config('xenforo.api_key', env('XENFORO_API_KEY')));

        return ! empty($base_url) && ! empty($api_key);
    }

    /**
     * Determine if Google OAuth is properly configured.
     *
     * Checks that all required Google OAuth credentials (client ID, client secret,
     * and redirect URL) are present in the application configuration.
     *
     * @return bool True if Google OAuth is configured, false otherwise
     */
    public function isGoogleOAuthConfigured(): bool
    {
        $google_oauth_configured = !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'))
            && !empty(config('services.google.redirect'));

        return $google_oauth_configured;
    }

    /**
     * Determine if Google OAuth authentication can be used.
     *
     * Google OAuth is available when XenForo OAuth is not required
     * and Google OAuth credentials are properly configured.
     *
     * @return bool True if Google OAuth can be used, false otherwise
     */
    public function isGoogleOAuthEnabled(): bool
    {
        return !$this->isXenforoOAuthRequired() && $this->isGoogleOAuthConfigured();
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
     * Email/password authentication is available when XenForo OAuth
     * is not required by the application configuration.
     *
     * @return bool True if email/password authentication can be used, false otherwise
     */
    public function isEmailPasswordAuthEnabled(): bool
    {
        return !$this->isXenforoOAuthRequired();
    }

    /**
     * Determine if Discord integration is properly configured.
     *
     * Checks that a Discord webhook URL is configured in either the
     * discord.webhooks.default or discord.webhook_url configuration.
     *
     * @return bool True if Discord webhook is configured, false otherwise
     */
    public function isDiscordIntegrationConfigured(): bool
    {
        $is_discord_integration_configured = !empty(config('discord.webhooks.default') ?? config('discord.webhook_url'));

        return $is_discord_integration_configured;
    }
}
