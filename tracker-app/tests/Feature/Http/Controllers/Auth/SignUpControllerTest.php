<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the SignUpController.
 *
 * Validates that the sign-up page displays correctly.
 */
class SignUpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_signup_page(): void
    {
        // Act
        $response = $this->get(route('auth.signup'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.auth.signup');
    }
}
