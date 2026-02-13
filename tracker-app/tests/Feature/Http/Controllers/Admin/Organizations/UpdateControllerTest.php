<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = $this->get(route('admin.organizations.update', $organization));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_update_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.organizations.update', $organization));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.organizations.update');
        $response->assertViewHas('organization', $organization);
    }

    public function test_invoke_administrator_can_update_any_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.organizations.update', $organization));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_can_update_moderated_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.organizations.update', $organization));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_cannot_update_non_moderated_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.organizations.update', $other_org));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_organization(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.organizations.update', $organization));

        // Assert
        $response->assertForbidden();
    }
}
