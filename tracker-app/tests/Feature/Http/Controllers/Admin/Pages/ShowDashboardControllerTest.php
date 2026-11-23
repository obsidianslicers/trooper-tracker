<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Pages;

use App\Enums\SystemCheckStatus;
use App\Messages\System\Queries\GetSystemCheckProblems;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Services\SystemCheckResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ShowDashboardControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_displays_admin_dashboard_for_admin_trooper(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->has('counts')
            ->has('cards')
            ->has('plugin_cards')
        );
    }

    public function test_invoke_counts_pending_permission_requests_for_admins(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        PermissionRequest::factory()->create();
        PermissionRequest::factory()->asApproved()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('counts.pending_permission_requests', 1)
            ->where('cards', fn ($cards) => collect($cards)->contains('key', 'permission-approvals'))
        );
    }

    public function test_invoke_hides_permission_approvals_from_moderators(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        PermissionRequest::factory()->create();

        $response = $this->actingAs($moderator)->get(route('admin.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('counts.pending_permission_requests', 0)
            ->where('cards', fn ($cards) => collect($cards)->contains('key', 'request-permissions')
                && !collect($cards)->contains('key', 'permission-approvals'))
        );
    }

    public function test_invoke_shows_system_check_problems_to_system_owners(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([GetSystemCheckProblems::class => collect([
            new SystemCheckResult('Queue worker heartbeat', SystemCheckStatus::FAIL, 'Last seen 12 minute(s) ago'),
            new SystemCheckResult('Debug mode', SystemCheckStatus::WARN),
        ])]);

        $response = $this->actingAs($owner)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('system_check_problems', 2));
        $response->assertDontSee('Last seen 12 minute(s) ago');
    }

    public function test_invoke_shows_no_problem_list_when_system_check_passes(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([GetSystemCheckProblems::class => collect()]);

        $response = $this->actingAs($owner)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('system_check_problems', 0)
            ->where('cards', fn ($cards) => collect($cards)->contains('key', 'system-check'))
        );
    }

    public function test_invoke_skips_system_check_for_staff_officers(): void
    {
        $officer = Trooper::factory()->asStaffOfficer()->create();
        $fake = $this->fakeMessages([GetSystemCheckProblems::class => collect()]);

        $response = $this->actingAs($officer)->get(route('admin.dashboard'));

        $response->assertOk();
        $fake->assertNotCalled(GetSystemCheckProblems::class);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_counts_pending_join_requests_from_join_requests_table(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page->where('counts.pending_join_requests', 1));
    }

    public function test_invoke_does_not_count_pending_signup_join_requests_as_standalone_requests(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $pending_trooper = Trooper::factory()->asPending()->create();
        $active_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        TrooperRequest::factory()
            ->forTrooper($pending_trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        TrooperRequest::factory()
            ->forTrooper($active_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('counts.not_approved', 1)
            ->where('counts.pending_join_requests', 1)
        );
    }
}
