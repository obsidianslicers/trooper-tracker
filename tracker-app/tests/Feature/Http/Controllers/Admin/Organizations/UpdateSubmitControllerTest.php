<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.organizations.update', $organization), [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_redirects_to_organizations_list(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.update', $organization), [
            'name' => $organization->name,
            'abbreviation' => $organization->abbreviation,
        ]);

        // Assert
        $response->assertRedirect(route('admin.organizations.list'));
    }

    public function test_invoke_administrator_can_update_any_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create(['name' => 'Original Name']);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.organizations.update', $organization), [
            'name' => 'Updated Name',
            'abbreviation' => $organization->abbreviation,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Organization::class, [
            Organization::ID => $organization->id,
            Organization::NAME => 'Updated Name',
        ]);
    }

    public function test_invoke_moderator_can_update_moderated_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create(['name' => 'Original Name']);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.organizations.update', $organization), [
            'name' => 'Moderator Updated Name',
            'abbreviation' => $organization->abbreviation,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Organization::class, [
            Organization::ID => $organization->id,
            Organization::NAME => 'Moderator Updated Name',
        ]);
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
        $response = $this->actingAs($moderator)->post(route('admin.organizations.update', $other_org), [
            'name' => 'Should Not Update',
            'abbreviation' => $other_org->abbreviation,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_organization(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.organizations.update', $organization), [
            'name' => 'Should Not Update',
            'abbreviation' => $organization->abbreviation,
        ]);

        // Assert
        $response->assertForbidden();
    }
}
