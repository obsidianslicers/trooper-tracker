<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $parent = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.organizations.create', $parent), [
            'name' => 'New Organization',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_creates_new_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $parent = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create', $parent), [
            'name' => 'New Region',
        ]);

        // Assert
        $this->assertDatabaseHas(Organization::class, [
            Organization::NAME => 'New Region',
            Organization::PARENT_ID => $parent->id,
            Organization::TYPE => OrganizationType::REGION->value,
        ]);
    }

    public function test_invoke_redirects_to_organization_update_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $parent = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create', $parent), [
            'name' => 'New Region',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertTrue(str_contains($response->headers->get('Location'), 'admin/organizations'));
    }

    public function test_invoke_creates_region_under_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $parent = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create', $parent), [
            'name' => 'New Region',
        ]);

        // Assert
        $this->assertDatabaseHas(Organization::class, [
            Organization::NAME => 'New Region',
            Organization::TYPE => OrganizationType::REGION->value,
        ]);
    }

    public function test_invoke_creates_unit_under_region(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $parent = Organization::factory()->create(['type' => OrganizationType::REGION]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create', $parent), [
            'name' => 'New Unit',
        ]);

        // Assert
        $this->assertDatabaseHas(Organization::class, [
            Organization::NAME => 'New Unit',
            Organization::TYPE => OrganizationType::UNIT->value,
        ]);
    }

    public function test_invoke_moderator_cannot_create_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $parent = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $parent->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.organizations.create', $parent), [
            'name' => 'Moderator Region',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_moderator_cannot_create_in_non_moderated_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.organizations.create', $other_org), [
            'name' => 'Should Not Create',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_create_organization(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $parent = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.organizations.create', $parent), [
            'name' => 'Should Not Create',
        ]);

        // Assert
        $response->assertForbidden();
    }
}
