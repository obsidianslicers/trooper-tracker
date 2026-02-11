<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_events_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.events');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewHas('trooper');
        $this->assertEquals($trooper->id, $response->viewData('trooper')->id);
    }

    public function test_invoke_passes_events_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organization_events');
    }

    public function test_invoke_administrator_can_view_any_trooper_events(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_cannot_view_non_member_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_moderator_can_view_moderated_trooper_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $trooper = Trooper::factory()->withAssignment($organization)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.events', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organization_events');
    }

    public function test_invoke_regular_trooper_cannot_view_other_events(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper1)->get(route('admin.troopers.events', $trooper2));

        // Assert
        $response->assertForbidden();
    }
}
