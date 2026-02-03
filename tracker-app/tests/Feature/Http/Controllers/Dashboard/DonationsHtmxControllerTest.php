<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Trooper;
use App\Models\TrooperDonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for DonationsHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can view donations HTMX partial
 * - Donations for the specified trooper are passed to the view
 */
class DonationsHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_htmx_partial(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.donations-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.donations');
    }

    public function test_invoke_passes_donations_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        TrooperDonation::factory()->for($trooper)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.donations-htmx'));

        // Assert
        $response->assertViewHas('donations');
        $donations = $response->viewData('donations');
        $this->assertCount(1, $donations);
    }

    public function test_invoke_shows_donations_for_specified_trooper(): void
    {
        // Arrange
        $auth_trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();
        TrooperDonation::factory()->for($other_trooper)->create();

        // Act
        $response = $this->actingAs($auth_trooper)
            ->get(route('dashboard.donations-htmx', ['trooper_id' => $other_trooper->id]));

        // Assert
        $response->assertViewHas('donations');
        $donations = $response->viewData('donations');
        $this->assertCount(1, $donations);
        $this->assertEquals($other_trooper->id, $donations->first()->trooper_id);
    }
}
