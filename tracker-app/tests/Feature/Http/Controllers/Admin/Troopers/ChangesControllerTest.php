<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->get(route('admin.troopers.changes', $trooper));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_changes_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.changes', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.changes');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.changes', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewHas('trooper');
        $this->assertEquals($trooper->id, $response->viewData('trooper')->id);
    }

    public function test_invoke_passes_changes_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.changes', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewHas('changes');
    }

    public function test_invoke_administrator_can_view_any_trooper_changes(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.changes', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_cannot_view_non_member_changes(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.changes', $trooper));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_view_other_changes(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper1)->get(route('admin.troopers.changes', $trooper2));

        // Assert
        $response->assertForbidden();
    }
}
