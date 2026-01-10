<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the ThankYouController.
 *
 * Validates that the thank you page displays correctly after registration.
 */
class ThankYouControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_thank_you_page(): void
    {
        // Act
        $response = $this->get(route('auth.thank-you'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.auth.thank-you');
    }
}
