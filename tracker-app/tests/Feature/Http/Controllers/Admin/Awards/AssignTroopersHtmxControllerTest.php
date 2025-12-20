<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTroopersHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_trooper_badge_for_valid_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper = Trooper::factory()->withOrganization($organization)->create();

        $data = [
            'trooper_id' => $trooper->id,
            'trooper_name' => $trooper->name,
        ];

        // Act
        $response = $this->get(route('admin.awards.assign-troopers-htmx', $award) . '?' . http_build_query($data));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.assign-troopers-htmx');
        $response->assertViewHas('trooper', $trooper);
        $response->assertViewHas('award', $award);
        $response->assertSee($trooper->name);
        $response->assertSee('badge');
    }

    public function test_invoke_validates_trooper_belongs_to_award_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($admin);

        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $award = Award::factory()->for($organization1)->create();

        $trooper = Trooper::factory()->withOrganization($organization2)->create();

        $data = [
            'trooper_id' => $trooper->id,
            'trooper_name' => $trooper->name,
        ];

        // Act & Assert
        $response = $this->get(route('admin.awards.assign-troopers-htmx', $award) . '?' . http_build_query($data));
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_for_unauthorized_user(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();

        $trooper = Trooper::factory()->withOrganization($organization)->create();

        $data = [
            'trooper_id' => $trooper->id,
            'trooper_name' => $trooper->name,
        ];

        // Act
        $response = $this->get(route('admin.awards.assign-troopers-htmx', $award) . '?' . http_build_query($data));

        // Assert
        $response->assertForbidden();
    }
}