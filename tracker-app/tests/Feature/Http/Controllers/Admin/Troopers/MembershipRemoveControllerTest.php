<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\MembershipRemoveController
 */
class MembershipRemoveControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $response = $this->post(route('admin.troopers.membership.remove', [$trooper, $organization]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_removes_membership_and_redirects(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.membership.remove', [$trooper, $organization]));

        $response->assertRedirect(route('admin.troopers.membership', $trooper));

        $this->assertSoftDeleted('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_requires_administrator_role(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->create();

        $response = $this->actingAs($moderator)
            ->post(route('admin.troopers.membership.remove', [$trooper, $organization]));

        $response->assertForbidden();
    }
}
