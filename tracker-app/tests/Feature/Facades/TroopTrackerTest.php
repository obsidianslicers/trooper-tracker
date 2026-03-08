<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\TroopTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TroopTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_xenforo_oauth_configured_returns_true_when_all_values_exist(): void
    {
        config([
            'services.xenforo.client_id' => 'client-id',
            'services.xenforo.client_secret' => 'secret',
            'services.xenforo.redirect' => 'https://example.com/callback',
        ]);

        $subject = new TroopTracker;

        $this->assertTrue($subject->isXenforoOAuthConfigured());
    }

    public function test_is_xenforo_oauth_required_requires_flag_and_configuration(): void
    {
        config([
            'tracker.auth.require_xenforo' => true,
            'services.xenforo.client_id' => 'client-id',
            'services.xenforo.client_secret' => 'secret',
            'services.xenforo.redirect' => 'https://example.com/callback',
        ]);

        $subject = new TroopTracker;

        $this->assertTrue($subject->isXenforoOAuthRequired());

        config(['tracker.auth.require_xenforo' => false]);
        $this->assertFalse($subject->isXenforoOAuthRequired());
    }

    public function test_is_google_oauth_enabled_requires_google_configuration_and_no_required_xenforo(): void
    {
        config([
            'tracker.auth.require_xenforo' => false,
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect' => 'https://example.com/google-callback',
            'services.xenforo.client_id' => null,
            'services.xenforo.client_secret' => null,
            'services.xenforo.redirect' => null,
        ]);

        $subject = new TroopTracker;

        $this->assertTrue($subject->isGoogleOAuthConfigured());
        $this->assertTrue($subject->isGoogleOAuthEnabled());

        config([
            'tracker.auth.require_xenforo' => true,
            'services.xenforo.client_id' => 'client-id',
            'services.xenforo.client_secret' => 'secret',
            'services.xenforo.redirect' => 'https://example.com/callback',
        ]);

        $this->assertFalse($subject->isGoogleOAuthEnabled());
    }

    public function test_is_email_password_auth_enabled_is_inverse_of_required_xenforo_oauth(): void
    {
        config([
            'tracker.auth.require_xenforo' => true,
            'services.xenforo.client_id' => 'client-id',
            'services.xenforo.client_secret' => 'secret',
            'services.xenforo.redirect' => 'https://example.com/callback',
        ]);

        $subject = new TroopTracker;

        $this->assertFalse($subject->isEmailPasswordAuthEnabled());

        config(['tracker.auth.require_xenforo' => false]);

        $this->assertTrue($subject->isEmailPasswordAuthEnabled());
    }

    public function test_is_xenforo_integration_configured_requires_base_url_and_api_key(): void
    {
        config([
            'services.xenforo.base_url' => 'https://forums.example.com',
            'services.xenforo.api_key' => 'api-key',
        ]);

        $subject = new TroopTracker;

        $this->assertTrue($subject->isXenforoIntegrationConfigured());

        config(['services.xenforo.api_key' => null]);

        $this->assertFalse($subject->isXenforoIntegrationConfigured());
    }

    public function test_is_discord_integration_configured_supports_both_configuration_locations(): void
    {
        $subject = new TroopTracker;

        config([
            'discord.webhooks.default' => 'https://discord.example/webhook-1',
            'discord.webhook_url' => null,
        ]);
        $this->assertTrue($subject->isDiscordIntegrationConfigured());

        config([
            'discord.webhooks.default' => null,
            'discord.webhook_url' => 'https://discord.example/webhook-2',
        ]);
        $this->assertTrue($subject->isDiscordIntegrationConfigured());

        config([
            'discord.webhooks.default' => null,
            'discord.webhook_url' => null,
        ]);
        $this->assertFalse($subject->isDiscordIntegrationConfigured());
    }

    public function test_is_google_sync_configured_matches_credentials_file_readability(): void
    {
        $subject = new TroopTracker;

        $expected = is_readable(base_path('google-credentials.json'));

        $this->assertSame($expected, $subject->isGoogleSyncConfigured());
    }
}
