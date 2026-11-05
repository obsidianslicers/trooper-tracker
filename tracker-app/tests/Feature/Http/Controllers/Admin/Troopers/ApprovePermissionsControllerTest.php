<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesBadgeRequests;
use Tests\Fixtures\PermissionRequests\BadgeRequests;
use Tests\TestCase;

class ApprovePermissionsControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesBadgeRequests;

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

    public function test_invoke_leaves_request_pending_when_an_extension_fails(): void
    {
        $this->addBadgeRequests();
        BadgeRequests::$approval_failure = 'The badge office is closed.';

        $admin = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()->withRole(null)->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.permissions.approve', $permission_request));

        $response->assertRedirect(route('admin.troopers.permissions.approvals'));
        $response->assertSessionHas('danger', 'Nothing was changed: The badge office is closed.');
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

    public function test_invoke_rejects_role_change_for_trooper_who_became_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MEMBER)
            ->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.permissions.approve', $permission_request));

        $response->assertSessionHasErrors('permission_request');
        $this->assertSame(MembershipRole::ADMINISTRATOR, $trooper->fresh()->membership_role);
        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
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
