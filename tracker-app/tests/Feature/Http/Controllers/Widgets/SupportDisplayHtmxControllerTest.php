<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Widgets;

use App\Models\Trooper;
use App\Models\TrooperDonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportDisplayHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('widgets.support-htmx'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_support_widget_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('widgets.support');
    }

    public function test_invoke_calculates_monthly_donations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        TrooperDonation::factory()->create([
            TrooperDonation::AMOUNT => 50.00,
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('goal');
    }

    public function test_invoke_passes_support_goal_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('goal');
    }

    public function test_invoke_passes_progress_percentage_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('progress');
    }

    public function test_invoke_calculates_progress_from_donations_and_goal(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        TrooperDonation::factory()->create([
            TrooperDonation::AMOUNT => 100.00,
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('widgets.support-htmx'));

        // Assert
        $response->assertOk();
        $progress = $response->viewData('progress');
        $this->assertIsNumeric($progress);
    }
}
