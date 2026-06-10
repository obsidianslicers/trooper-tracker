<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_admin_dashboard_for_admin_trooper(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.display'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.display');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.display'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_counts_pending_join_requests_from_join_requests_table(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        JoinRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.display'));

        $response->assertViewHas('pending_join_requests', 1);
    }
}
