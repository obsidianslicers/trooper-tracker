<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Feature tests for the LogoutController.
 *
 * Validates that authenticated troopers can successfully log out
 * and are redirected appropriately with a flash message.
 */
class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_logs_out_authenticated_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);
        $this->assertTrue(Auth::check());

        // Act
        $response = $this->get(route('auth.logout'));

        // Assert
        $response->assertRedirect(route('auth.login', ['logged_out' => '1']));
        $this->assertFalse(Auth::check());
    }

    public function test_invoke_sets_success_flash_message(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('auth.logout'));

        // Assert
        $response->assertSessionHas('flash_messages');
    }
}
