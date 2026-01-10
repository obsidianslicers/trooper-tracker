<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the InactiveController.
 *
 * Validates that the thank you page displays correctly after registration.
 */
class InactiveControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_thank_you_page(): void
    {
        // Act
        $response = $this->get(route('auth.inactive'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.auth.inactive');
    }
}
