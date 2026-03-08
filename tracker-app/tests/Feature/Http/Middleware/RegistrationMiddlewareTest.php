<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RegistrationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_rejects_non_xenforo_registration_when_xenforo_is_required(): void
    {
        // Arrange
        config()->set('tracker.auth.require_xenforo', true);

        Session::put('registration_auth', [
            'method' => 'google',
            'email' => 'newuser@example.com',
            'expires_at' => now()->addMinutes(20),
        ]);

        // Act
        $response = $this->get(route('auth.register'));

        // Assert
        $response->assertRedirect(route('auth.signup'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('registration_auth');
    }
}
