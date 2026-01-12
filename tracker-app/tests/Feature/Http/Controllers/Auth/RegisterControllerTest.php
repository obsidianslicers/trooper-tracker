<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the RegisterController.
 *
 * Validates that the registration page displays correctly with
 * organizations and pre-filled OAuth registration data.
 */
class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_registration_page_with_organizations(): void
    {
        // Arrange
        $organizations = Organization::factory()->count(3)->create();

        session([
            'registration_auth' => [
                'email' => 'test@example.com',
                'method' => 'google',
                'provider_id' => 'google-123',
                'expires_at' => now()->addMinutes(20),
            ],
        ]);

        // Act
        $response = $this->get(route('auth.register'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.auth.register');
        $response->assertViewHas('organization_hierarchy', function ($view_orgs) use ($organizations)
        {
            return $view_orgs->count() === 3;
        });
        $response->assertViewHas('email', 'test@example.com');
        $response->assertViewHas('registration_method', 'google');
    }

    public function test_invoke_displays_registration_page_with_email_from_session(): void
    {
        // Arrange
        Organization::factory()->create();

        session([
            'registration_auth' => [
                'email' => 'oauth@example.com',
                'method' => 'xenforo',
                'provider_id' => 'xenforo-456',
                'expires_at' => now()->addMinutes(20),
            ],
        ]);

        // Act
        $response = $this->get(route('auth.register'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.auth.register');
        $response->assertViewHas('email', 'oauth@example.com');
        $response->assertViewHas('registration_method', 'xenforo');
    }
}
