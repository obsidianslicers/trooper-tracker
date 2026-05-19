<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\JoinRequestApproveHtmxController
 */
class JoinRequestApproveHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $member = Trooper::factory()->asMember()->create();
        $join_request = TrooperOrganization::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $response = $this->post(route('admin.troopers.join-requests.approve-htmx', $join_request));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_approves_join_request_and_returns_card_fragment(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.join-requests.approve-htmx', $join_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.join-request-card');
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));
    }

    public function test_invoke_requires_moderate_authorization(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $other_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = TrooperOrganization::factory()
            ->forTrooper($other_member)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $response = $this->actingAs($member)
            ->post(route('admin.troopers.join-requests.approve-htmx', $join_request));

        $response->assertForbidden();
    }
}
