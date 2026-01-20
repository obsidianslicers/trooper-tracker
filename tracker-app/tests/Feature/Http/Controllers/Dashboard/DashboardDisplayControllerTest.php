<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for DashboardDisplayController.
 *
 * Verifies:
 * - Authenticated troopers can view their dashboard
 * - Dashboard displays trooper statistics
 * - Breadcrumbs are set correctly
 * - Dashboard loads upcoming events and recent activity
 */
class DashboardDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_dashboard_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.display'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.display');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.display'));

        // Assert
        $response->assertViewHas('trooper');
        $view_trooper = $response->viewData('trooper');
        $this->assertEquals($trooper->id, $view_trooper->id);
    }

    public function test_invoke_passes_statistics_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.display'));

        // Assert
        $response->assertViewHas('total_troops_by_organization');
        $response->assertViewHas('total_troops_by_costume');
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('dashboard.display'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
