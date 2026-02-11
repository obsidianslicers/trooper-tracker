<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelledControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('events.cancelled'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_cancelled_events_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.cancelled');
    }

    public function test_invoke_passes_events_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('events');
    }

    public function test_invoke_passes_lookback_period_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('lookback', 30);
    }

    public function test_invoke_passes_costume_organizations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('costume_organizations');
    }

    public function test_invoke_pending_trooper_cannot_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        // Assert
        $response->assertUnauthorized();
    }
}
