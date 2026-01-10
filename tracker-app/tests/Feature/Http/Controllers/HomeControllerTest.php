<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the HomeController.
 *
 * Validates that the home page correctly displays for guests
 * and redirects authenticated troopers to the events list.
 */
class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_home_page_for_guest(): void
    {
        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.home');
    }

    public function test_invoke_redirects_authenticated_trooper_to_events_list(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('home'));

        // Assert
        $response->assertRedirect(route('events.list'));
    }
}
