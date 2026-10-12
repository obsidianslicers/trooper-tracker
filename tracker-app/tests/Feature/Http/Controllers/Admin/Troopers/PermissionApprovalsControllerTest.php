<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionApprovalsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_lists_pending_requests_for_admin(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $pending = PermissionRequest::factory()->create(['note' => 'Helps with events']);
        PermissionRequest::factory()->asApproved()->create();

        $response = $this->actingAs($admin)->get(route('admin.troopers.permissions.approvals'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/troopers/PermissionApprovals')
            ->has('permission_requests', 1)
            ->where('permission_requests.0.id', $pending->id)
            ->where('permission_requests.0.note', 'Helps with events'));
    }

    public function test_invoke_forbids_moderators(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.troopers.permissions.approvals'));

        $response->assertForbidden();
    }
}
