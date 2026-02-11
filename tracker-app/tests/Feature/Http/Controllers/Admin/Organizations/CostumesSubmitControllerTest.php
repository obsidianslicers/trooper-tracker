<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostumesSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.organizations.costumes', $organization), [
            'costumes' => [],
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_costume_names(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $costume = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Original Name',
        ]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.costumes', $organization), [
            'costumes' => [
                $costume->id => ['name' => 'Updated Name'],
            ],
        ]);

        // Assert
        $this->assertDatabaseHas(OrganizationCostume::class, [
            OrganizationCostume::ID => $costume->id,
            OrganizationCostume::NAME => 'Updated Name',
        ]);
    }

    public function test_invoke_redirects_to_costumes_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.costumes', $organization), [
            'costumes' => [],
        ]);

        // Assert
        $response->assertRedirect(route('admin.organizations.costumes', $organization));
    }

    public function test_invoke_administrator_can_update_costumes(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $costume = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.costumes', $organization), [
            'costumes' => [
                $costume->id => ['name' => 'Admin Updated'],
            ],
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(OrganizationCostume::class, [
            OrganizationCostume::ID => $costume->id,
            OrganizationCostume::NAME => 'Admin Updated',
        ]);
    }

    public function test_invoke_moderator_can_update_costumes_for_moderated_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $costume = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.organizations.costumes', $organization), [
            'costumes' => [
                $costume->id => ['name' => 'Moderator Updated'],
            ],
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(OrganizationCostume::class, [
            OrganizationCostume::ID => $costume->id,
            OrganizationCostume::NAME => 'Moderator Updated',
        ]);
    }

    public function test_invoke_moderator_cannot_update_costumes_for_non_moderated_organization(): void
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
        $response = $this->actingAs($moderator)->post(route('admin.organizations.costumes', $other_org), [
            'costumes' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.organizations.costumes', $organization), [
            'costumes' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }
}
