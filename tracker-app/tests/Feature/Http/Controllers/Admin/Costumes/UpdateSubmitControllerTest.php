<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_costume_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create(['name' => 'Old Name']);
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/costumes/' . $costume->id . '/update', [
            'name' => 'New Name',
            'organizations' => [
                $organization->id => ['selected' => '1'],
                $other_organization->id => ['selected' => '1'],
            ],
        ]);

        $response->assertRedirect(route('admin.costumes.list'));
        $this->assertDatabaseHas('tt_costumes', [
            'id' => $costume->id,
            'name' => 'New Name',
        ]);
        $this->assertEqualsCanonicalizing(
            [$organization->id, $other_organization->id],
            $costume->fresh()->load('organizations')->organizations->pluck('id')->all()
        );
    }

    public function test_invoke_removes_unselected_organizations(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create(['name' => 'Old Name']);
        $organization = Organization::factory()->create();
        $removed_organization = Organization::factory()->create();

        $costume->organizations()->sync([$organization->id, $removed_organization->id]);

        $response = $this->actingAs($trooper)->post('/admin/costumes/' . $costume->id . '/update', [
            'name' => 'New Name',
            'organizations' => [
                $organization->id => ['selected' => '1'],
            ],
        ]);

        $response->assertRedirect(route('admin.costumes.list'));
        // Verify the selected organization still exists and is not deleted
        $this->assertDatabaseHas('tt_organization_costumes', [
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
            OrganizationCostume::DELETED_AT => null,
        ]);
        // Verify the unselected organization was soft deleted
        $this->assertDatabaseHas('tt_organization_costumes', [
            OrganizationCostume::ORGANIZATION_ID => $removed_organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $this->assertSoftDeleted('tt_organization_costumes', [
            OrganizationCostume::ORGANIZATION_ID => $removed_organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->post('/admin/costumes/' . $costume->id . '/update', [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
