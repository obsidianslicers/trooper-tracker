<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Costumes UpdateController.
 *
 * Verifies:
 * - Administrator can view the costume update form
 * - Non-administrator cannot view the update form
 * - Costume model is passed to the view
 * - Correct view is rendered
 * - Authentication is required
 * - Authorization is enforced
 */
class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $costume = Costume::factory()->create();

        // Act
        $response = $this->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_update_form(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.costumes.update');
    }

    public function test_invoke_moderator_cannot_view_update_form(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $costume = Costume::factory()->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_view_update_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $costume = Costume::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_pending_trooper_cannot_view_update_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();
        $costume = Costume::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_retired_trooper_cannot_view_update_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->create();
        $costume = Costume::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_passes_costume_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create(['name' => 'Test Costume']);

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertViewHas('costume');
        $view_costume = $response->viewData('costume');
        $this->assertInstanceOf(Costume::class, $view_costume);
        $this->assertEquals('Test Costume', $view_costume->name);
        $this->assertEquals($costume->id, $view_costume->id);
    }

    public function test_invoke_sets_breadcrumbs(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.update', ['costume' => $costume->id]));

        // Assert
        $response->assertOk();
        // The actual breadcrumb verification would depend on the view structure
        // This test mainly ensures the initialized() method runs without error
    }

    public function test_invoke_with_nonexistent_costume(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.costumes.update', ['costume' => 99999]));

        // Assert
        $response->assertNotFound();
    }
}
