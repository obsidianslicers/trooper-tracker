<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCostumeSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.organizations.create-costume', $organization), [
            'name' => 'New Costume',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_creates_new_costume(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create-costume', $organization), [
            'name' => 'TK Stormtrooper',
        ]);

        // Assert
        $this->assertDatabaseHas(OrganizationCostume::class, [
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'TK Stormtrooper',
        ]);
    }

    public function test_invoke_redirects_to_costumes_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create-costume', $organization), [
            'name' => 'New Costume',
        ]);

        // Assert
        $response->assertRedirect(route('admin.organizations.costumes', $organization));
    }

    public function test_invoke_administrator_can_create_costume(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.create-costume', $organization), [
            'name' => 'Admin Costume',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(OrganizationCostume::class, [
            OrganizationCostume::NAME => 'Admin Costume',
        ]);
    }

    public function test_invoke_moderator_can_create_costume_for_moderated_organization(): void
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
        $response = $this->actingAs($moderator)->post(route('admin.organizations.create-costume', $organization), [
            'name' => 'Moderator Costume',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(OrganizationCostume::class, [
            OrganizationCostume::NAME => 'Moderator Costume',
        ]);
    }

    public function test_invoke_moderator_cannot_create_costume_for_non_moderated_organization(): void
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
        $response = $this->actingAs($moderator)->post(route('admin.organizations.create-costume', $other_org), [
            'name' => 'Should Not Create',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_create_costume(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.organizations.create-costume', $organization), [
            'name' => 'Should Not Create',
        ]);

        // Assert
        $response->assertForbidden();
    }
}
