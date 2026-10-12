<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovePermissionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_approves_request(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR)
            ->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.permissions.approve', $permission_request));

        $response->assertRedirect(route('admin.troopers.permissions.approvals'));
        $this->assertSame(PermissionRequestStatus::APPROVED, $permission_request->fresh()->status);
        $this->assertSame(MembershipRole::MODERATOR, $trooper->fresh()->membership_role);
    }

    public function test_invoke_leaves_request_pending_when_forum_update_fails(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        $admin = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()
            ->withRole(null)
            ->withForumGroups(add: [['id' => 20, 'title' => 'Event Coordinators']])
            ->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.permissions.approve', $permission_request));

        $response->assertRedirect(route('admin.troopers.permissions.approvals'));
        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
    }

    public function test_invoke_rejects_already_reviewed_request(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()->asDenied()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.permissions.approve', $permission_request));

        $response->assertSessionHasErrors('permission_request');
    }

    public function test_invoke_forbids_moderators(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $permission_request = PermissionRequest::factory()->create();

        $response = $this->actingAs($moderator)
            ->post(route('admin.troopers.permissions.approve', $permission_request));

        $response->assertForbidden();
        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
    }
}
