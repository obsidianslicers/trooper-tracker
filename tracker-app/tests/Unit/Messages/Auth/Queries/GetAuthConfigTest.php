<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Queries;

use App\Facades\TroopTracker;
use App\Messages\Auth\Queries\GetAuthConfig;
use Illuminate\Support\Facades\Session;
use Mockery;
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

        $tracker = Mockery::mock(TroopTracker::class);
        $tracker->shouldReceive('getXenforoOAuthName')->once()->andReturn('Florida Garrison');
        $tracker->shouldReceive('isXenforoOAuthRequired')->once()->andReturn(false);
        $tracker->shouldReceive('isXenforoOAuthConfigured')->once()->andReturn(true);
        $tracker->shouldReceive('isGoogleOAuthEnabled')->once()->andReturn(true);
        $tracker->shouldReceive('isGoogleOAuthConfigured')->once()->andReturn(true);
        $tracker->shouldReceive('isEmailPasswordAuthEnabled')->once()->andReturn(true);

        $subject = new GetAuthConfig();

        $result = $subject->handle($tracker);

        $this->assertSame($registration_auth, $result['session']);
        $this->assertSame('Florida Garrison', $result['xenforo']['name']);
        $this->assertFalse($result['xenforo']['required']);
        $this->assertTrue($result['xenforo']['configured']);
        $this->assertTrue($result['google']['enabled']);
        $this->assertTrue($result['google']['configured']);
        $this->assertTrue($result['email_password']['enabled']);
    }

    public function test_handle_returns_empty_session_when_registration_auth_is_missing(): void
    {
        Session::forget('registration_auth');

        $tracker = Mockery::mock(TroopTracker::class);
        $tracker->shouldReceive('getXenforoOAuthName')->once()->andReturn('');
        $tracker->shouldReceive('isXenforoOAuthRequired')->once()->andReturn(false);
        $tracker->shouldReceive('isXenforoOAuthConfigured')->once()->andReturn(false);
        $tracker->shouldReceive('isGoogleOAuthEnabled')->once()->andReturn(false);
        $tracker->shouldReceive('isGoogleOAuthConfigured')->once()->andReturn(false);
        $tracker->shouldReceive('isEmailPasswordAuthEnabled')->once()->andReturn(true);

        $subject = new GetAuthConfig();

        $result = $subject->handle($tracker);

        $this->assertSame([], $result['session']);
        $this->assertSame('', $result['xenforo']['name']);
        $this->assertFalse($result['xenforo']['required']);
        $this->assertFalse($result['xenforo']['configured']);
        $this->assertFalse($result['google']['enabled']);
        $this->assertFalse($result['google']['configured']);
        $this->assertTrue($result['email_password']['enabled']);
    }
}
