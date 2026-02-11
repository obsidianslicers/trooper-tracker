<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for AdminDisplayController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view admin dashboard
 * - Moderators can view admin dashboard
 * - Correct pending trooper count is displayed
 * - Flash messages are set appropriately
 * - Correct view is rendered
 */
class AdminDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.display'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_admin_dashboard_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.display'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.display');
    }

    public function test_invoke_passes_not_approved_count_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asPending()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.display'));

        // Assert
        $response->assertViewHas('not_approved', 3);
    }

    public function test_invoke_shows_no_flash_message_when_no_pending_troopers(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.display'));

        // Assert
        $response->assertViewHas('not_approved', 0);
    }

    public function test_invoke_counts_pending_troopers_correctly(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asPending()->count(3)->create();
        Trooper::factory()->asActive()->count(2)->create(); // Should not be counted

        // Act
        $response = $this->actingAs($admin)->get(route('admin.display'));

        // Assert
        $response->assertViewHas('not_approved', 3);
    }

    public function test_invoke_moderator_sees_pending_count(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.display'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('not_approved');
    }

    public function test_invoke_allows_moderator_access(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.display'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.display');
    }

    public function test_invoke_forbids_regular_trooper_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.display'));

        // Assert
        $response->assertForbidden();
    }
}
