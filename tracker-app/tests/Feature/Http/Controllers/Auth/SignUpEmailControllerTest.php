<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the SignUpEmailController.
 *
 * Validates that the email-based signup flow correctly creates
 * a registration session and redirects to the registration form.
 */
class SignUpEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_registration_session_and_redirects(): void
    {
        // Act
        $response = $this->get(route('auth.signup-email'));

        // Assert
        $response->assertRedirect(route('auth.register'));
        $response->assertSessionHas('registration_auth');

        $registration_auth = session('registration_auth');
        $this->assertEquals('email', $registration_auth['method']);
        $this->assertNull($registration_auth['email']);
        $this->assertNotNull($registration_auth['expires_at']);
    }

    public function test_invoke_blocks_email_signup_when_xenforo_is_required(): void
    {
        // Arrange
        config()->set('tracker.auth.require_xenforo', true);

        // Act
        $response = $this->get(route('auth.signup-email'));

        // Assert
        $response->assertRedirect(route('auth.signup'));
        $response->assertSessionMissing('registration_auth');
    }
}
