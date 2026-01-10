<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the LoginController.
 *
 * Validates that the login page displays correctly for guests
 * and redirects authenticated troopers to the home page.
 */
class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_login_page_for_guest(): void
    {
        // Act
        $response = $this->get(route('auth.login'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.auth.login');
    }

    public function test_invoke_redirects_authenticated_trooper_to_home(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('auth.login'));

        // Assert
        $response->assertRedirect(route('home'));
    }
}
