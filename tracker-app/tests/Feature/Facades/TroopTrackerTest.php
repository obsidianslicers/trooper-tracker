<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\TroopTracker;
use App\Plugins\PluginLoginProviders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Auth\ForumLoginProvider;
use Tests\TestCase;

class TroopTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PluginLoginProviders::class)->add(ForumLoginProvider::class);
    }

    protected function tearDown(): void
    {
        ForumLoginProvider::reset();

        parent::tearDown();
    }

    public function test_required_login_provider_needs_the_flag_and_configuration(): void
    {
        $subject = new TroopTracker;

        $this->assertNull($subject->requiredLoginProvider());

        ForumLoginProvider::$required = true;
        $this->assertSame('forum', $subject->requiredLoginProvider()?->key());

        ForumLoginProvider::$configured = false;
        $this->assertNull($subject->requiredLoginProvider());
    }

    public function test_is_email_password_auth_enabled_unless_a_login_provider_is_required(): void
    {
        $subject = new TroopTracker;

        $this->assertTrue($subject->isEmailPasswordAuthEnabled());

        ForumLoginProvider::$required = true;

        $this->assertFalse($subject->isEmailPasswordAuthEnabled());
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
}
