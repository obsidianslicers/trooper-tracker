<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Queries;

use App\Facades\TroopTracker;
use App\Messages\Auth\Queries\GetAuthConfig;
use App\Plugins\PluginLoginProviders;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\Fixtures\Auth\ForumLoginProvider;
use Tests\TestCase;

class GetAuthConfigTest extends TestCase
{
    public function test_handle_returns_auth_config_with_session_payload(): void
    {
        $registration_auth = [
            'method' => 'email',
            'email' => 'trooper@example.com',
            'expires_at' => now()->addMinutes(20)->toIso8601String(),
        ];

        Session::put('registration_auth', $registration_auth);

        $providers = $this->providers();

        $tracker = Mockery::mock(TroopTracker::class);
        $tracker->shouldReceive('isEmailPasswordAuthEnabled')->once()->andReturn(true);

        $subject = new GetAuthConfig;

        $result = $subject->handle($tracker, $providers);

        $this->assertSame($registration_auth, $result['session']);
        $this->assertSame([[
            'key' => 'forum',
            'name' => 'Florida Garrison',
            'icon_url' => 'https://example.com/forum.png',
        ]], $result['providers']);
        $this->assertNull($result['required']);
        $this->assertTrue($result['email_password']['enabled']);
    }

    public function test_handle_names_the_required_provider(): void
    {
        Session::forget('registration_auth');

        ForumLoginProvider::$required = true;
        $providers = $this->providers();

        $tracker = Mockery::mock(TroopTracker::class);
        $tracker->shouldReceive('isEmailPasswordAuthEnabled')->once()->andReturn(false);

        $subject = new GetAuthConfig;

        $result = $subject->handle($tracker, $providers);

        $this->assertSame([], $result['session']);
        $this->assertSame(['key' => 'forum', 'name' => 'Florida Garrison'], $result['required']);
        $this->assertFalse($result['email_password']['enabled']);
    }

    protected function tearDown(): void
    {
        ForumLoginProvider::reset();

        parent::tearDown();
    }

    private function providers(): PluginLoginProviders
    {
        config(['services.google.client_id' => null]);

        $providers = new PluginLoginProviders($this->app);
        $providers->add(ForumLoginProvider::class);

        return $providers;
    }
}
