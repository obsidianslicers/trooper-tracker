<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Jobs\SendPermissionRequestReviewedNotificationsJob;
use App\Messages\Troopers\Commands\Permissions\DenyTrooperPermissions;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use RuntimeException;
use Tests\TestCase;

class DenyTrooperPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_denies_request_without_changing_trooper(): void
    {
        Bus::fake([SendPermissionRequestReviewedNotificationsJob::class]);

        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR)
            ->create();

        $subject = new DenyTrooperPermissions($admin, $permission_request, 'Not yet');
        $subject->handle();

        $permission_request->refresh();
        $this->assertSame(PermissionRequestStatus::DENIED, $permission_request->status);
        $this->assertSame('Not yet', $permission_request->denial_reason);
        $this->assertSame($admin->id, $permission_request->reviewer_trooper_id);
        $this->assertSame(MembershipRole::MEMBER, $trooper->fresh()->membership_role);
        Bus::assertDispatched(SendPermissionRequestReviewedNotificationsJob::class);
    }

    public function test_handle_rejects_request_that_is_not_pending(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $permission_request = PermissionRequest::factory()->asApproved()->create();

        $subject = new DenyTrooperPermissions($admin, $permission_request);

        $this->expectException(RuntimeException::class);

        $subject->handle();
    }
}
