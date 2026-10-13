<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\PermissionRequestStatus;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenyPermissionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_denies_request_with_reason(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.permissions.deny', $permission_request), [
                'denial_reason' => 'Talk to me first',
            ]);

        $response->assertRedirect(route('admin.troopers.permissions.approvals'));
        $permission_request->refresh();
        $this->assertSame(PermissionRequestStatus::DENIED, $permission_request->status);
        $this->assertSame('Talk to me first', $permission_request->denial_reason);
    }

    public function test_invoke_forbids_moderators(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $permission_request = PermissionRequest::factory()->create();

        $response = $this->actingAs($moderator)
            ->post(route('admin.troopers.permissions.deny', $permission_request));

        $response->assertForbidden();

        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
    }
}
