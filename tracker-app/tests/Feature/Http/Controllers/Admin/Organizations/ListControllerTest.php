<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.organizations.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_organizations_list_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.organizations.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.organizations.list');
        $response->assertViewHas('organizations');
    }

    public function test_invoke_administrator_can_see_all_organizations(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.organizations.list'));

        // Assert
        $response->assertOk();
        $response->assertSeeText($org->name);
    }

    public function test_invoke_moderator_can_access_list(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.organizations.list'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_passes_organizations_with_assignments(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Organization::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.organizations.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organizations', function ($organizations)
        {
            return $organizations->count() >= 3;
        });
    }

    public function test_invoke_regular_trooper_cannot_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.organizations.list'));

        // Assert
        $response->assertForbidden();
    }
}
