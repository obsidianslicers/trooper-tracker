<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_troopers_pending_approval(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asPending()->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.approvals'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.approvals');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.troopers.approvals'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_passes_join_requests_to_view(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperOrganization::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        $response->assertViewHas('join_requests');
        $this->assertCount(1, $response->viewData('join_requests'));
    }
}
