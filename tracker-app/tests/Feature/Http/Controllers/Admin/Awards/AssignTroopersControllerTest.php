<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_shows_assign_form_for_authorized_user(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper = Trooper::factory()->withOrganization($organization)->create();

        // Act
        $response = $this->get(route('admin.awards.assign-troopers', $award));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.assign-troopers');
        $response->assertViewHas('award', $award);
        $response->assertViewHas('troopers');
    }

    public function test_invoke_filters_troopers_by_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $award = Award::factory()->for($organization1)->create();

        $trooper1 = Trooper::factory()->withOrganization($organization1)->create();

        $trooper2 = Trooper::factory()->withOrganization($organization2)->create();

        // Act
        $response = $this->get(route('admin.awards.assign-troopers', $award));

        // Assert
        $response->assertOk();
        $troopers = $response->viewData('troopers');
        $this->assertTrue($troopers->contains($trooper1));
        $this->assertFalse($troopers->contains($trooper2));
    }

    public function test_invoke_searches_troopers_by_name(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper1 = Trooper::factory()->create(['name' => 'John Doe']);
        $trooper1->organizations()->attach($organization, ['identifier' => 'TK1111']);

        $trooper2 = Trooper::factory()->create(['name' => 'Jane Smith']);
        $trooper2->organizations()->attach($organization, ['identifier' => 'TK2222']);

        // Act
        $response = $this->get(route('admin.awards.assign-troopers', ['award' => $award, 'search' => 'John']));

        // Assert
        $response->assertOk();
        $troopers = $response->viewData('troopers');
        $this->assertTrue($troopers->contains($trooper1));
        $this->assertFalse($troopers->contains($trooper2));
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        // Act
        $response = $this->get(route('admin.awards.assign-troopers', $award));

        // Assert
        $response->assertForbidden();
    }
}