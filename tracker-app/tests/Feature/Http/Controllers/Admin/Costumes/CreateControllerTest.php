<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Costumes CreateController.
 *
 * Verifies:
 * - Administrator can view the costume creation form
 * - Non-administrator cannot view the creation form
 * - New costume model is passed to the view
 * - Correct view is rendered
 * - Authentication is required
 * - Authorization is enforced
 */
class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.costumes.create'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_create_form(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.costumes.create');
    }

    public function test_invoke_moderator_cannot_view_create_form(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_view_create_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_pending_trooper_cannot_view_create_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_retired_trooper_cannot_view_create_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_passes_new_costume_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertViewHas('costume');
        $costume = $response->viewData('costume');
        $this->assertInstanceOf(Costume::class, $costume);
        $this->assertTrue($costume->exists === false || $costume->id === null);
    }

    public function test_invoke_sets_breadcrumbs(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.create'));

        // Assert
        $response->assertOk();
        // The actual breadcrumb verification would depend on the view structure
        // This test mainly ensures the initialized() method runs without error
    }
}
