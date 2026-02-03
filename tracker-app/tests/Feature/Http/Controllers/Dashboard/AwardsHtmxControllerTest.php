<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for AwardsHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can view awards HTMX partial
 * - Awards for the specified trooper are passed to the view
 */
class AwardsHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_htmx_partial(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.awards-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.awards');
    }

    public function test_invoke_passes_awards_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $award = Award::factory()->create();
        AwardTrooper::factory()->for($trooper)->for($award)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.awards-htmx'));

        // Assert
        $response->assertViewHas('awards');
        $awards = $response->viewData('awards');
        $this->assertCount(1, $awards);
    }

    public function test_invoke_shows_awards_for_specified_trooper(): void
    {
        // Arrange
        $auth_trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();
        $award = Award::factory()->create();
        AwardTrooper::factory()->for($other_trooper)->for($award)->create();

        // Act
        $response = $this->actingAs($auth_trooper)
            ->get(route('dashboard.awards-htmx', ['trooper_id' => $other_trooper->id]));

        // Assert
        $response->assertViewHas('awards');
        $awards = $response->viewData('awards');
        $this->assertCount(1, $awards);
        $this->assertEquals($other_trooper->id, $awards->first()->trooper_id);
    }
}
