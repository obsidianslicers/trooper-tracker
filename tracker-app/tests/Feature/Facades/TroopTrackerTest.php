<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\TroopTracker;
use App\Plugins\PluginLoginProviders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Plugins\Xenforo\UsesXenforoPlugin;
use Tests\TestCase;

class TroopTrackerTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_required_login_provider_needs_the_flag_and_configuration(): void
    {
        $this->configureXenforoLogin();

        $subject = new TroopTracker;

        $this->assertSame('xenforo', $subject->requiredLoginProvider()?->key());

        config(['plugins.xenforo.require_xenforo' => false]);
        $this->assertNull($subject->requiredLoginProvider());

        config([
            'plugins.xenforo.require_xenforo' => true,
            'plugins.xenforo.client_secret' => null,
        ]);
        $this->assertNull($subject->requiredLoginProvider());
    }

    public function test_required_login_provider_is_null_without_the_plugin(): void
    {
        $this->configureXenforoLogin();
        $this->app->forgetInstance(PluginLoginProviders::class);

        $this->assertNull((new TroopTracker)->requiredLoginProvider());
    }

    public function test_is_email_password_auth_enabled_unless_a_login_provider_is_required(): void
    {
        $this->configureXenforoLogin();

        $subject = new TroopTracker;

        $this->assertFalse($subject->isEmailPasswordAuthEnabled());

        config(['plugins.xenforo.require_xenforo' => false]);

        $this->assertTrue($subject->isEmailPasswordAuthEnabled());
    }

    public function test_is_support_goal_enabled_requires_a_positive_goal(): void
    {
        $subject = new TroopTracker;

        config(['tracker.support.goal' => '300']);
        $this->assertTrue($subject->isSupportGoalEnabled());

        config(['tracker.support.goal' => null]);
        $this->assertFalse($subject->isSupportGoalEnabled());

        config(['tracker.support.goal' => '']);
        $this->assertFalse($subject->isSupportGoalEnabled());

        config(['tracker.support.goal' => '0']);
        $this->assertFalse($subject->isSupportGoalEnabled());
    }

    public function test_is_google_sync_configured_matches_credentials_file_readability(): void
    {
        $subject = new TroopTracker;

        $expected = is_readable(base_path('google-credentials.json'));

        $this->assertSame($expected, $subject->isGoogleSyncConfigured());
    }

    private function configureXenforoLogin(): void
    {
        config([
            'plugins.xenforo.require_xenforo' => true,
            'plugins.xenforo.client_id' => 'client-id',
            'plugins.xenforo.client_secret' => 'secret',
            'plugins.xenforo.redirect' => 'https://example.com/callback',
        ]);
    }
}
