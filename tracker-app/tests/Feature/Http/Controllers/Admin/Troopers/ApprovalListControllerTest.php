<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
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

        TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        $response->assertViewHas('trooper_requests');
        $this->assertCount(1, $response->viewData('trooper_requests'));
    }

    public function test_invoke_keeps_pending_signup_join_requests_in_trooper_approval_queue_only(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $pending_trooper = Trooper::factory()->asPending()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperRequest::factory()
            ->forTrooper($pending_trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        $this->assertCount(1, $response->viewData('troopers'));
        $this->assertCount(1, $response->viewData('troopers')->first()->trooper_requests);
        $this->assertCount(0, $response->viewData('trooper_requests'));
    }
}
