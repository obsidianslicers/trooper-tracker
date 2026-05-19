<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\RecruitSubmitController
 */
class RecruitSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(route('admin.troopers.recruit'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_directly_adds_trooper_and_redirects(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        $response = $this->actingAs($admin)->post(route('admin.troopers.recruit'), [
            'trooper_id'      => $trooper->id,
            'organization_id' => $organization->id,
        ]);

        $response->assertRedirect(route('admin.troopers.recruit'));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_validates_trooper_id_required(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $response = $this->actingAs($admin)->post(route('admin.troopers.recruit'), [
            'organization_id' => $organization->id,
        ]);

        $response->assertSessionHasErrors('trooper_id');
    }

    public function test_invoke_validates_organization_id_required(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($admin)->post(route('admin.troopers.recruit'), [
            'trooper_id' => $trooper->id,
        ]);

        $response->assertSessionHasErrors('organization_id');
    }

    public function test_invoke_requires_recruit_authorization_on_organization(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $response = $this->actingAs($moderator)->post(route('admin.troopers.recruit'), [
            'trooper_id'      => $trooper->id,
            'organization_id' => $organization->id,
        ]);

        $response->assertForbidden();
    }
}
