<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Events\TrooperPermissionsApproved;
use App\Exceptions\PermissionApprovalException;
use App\Jobs\SendPermissionRequestReviewedNotificationsJob;
use App\Messages\Troopers\Commands\Permissions\ApproveTrooperPermissions;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event as EventFacade;
use RuntimeException;
use Tests\Concerns\UsesBadgeRequests;
use Tests\Fixtures\PermissionRequests\BadgeRequests;
use Tests\TestCase;

class ApproveTrooperPermissionsTest extends TestCase
{
    use RefreshDatabase;
    use UsesBadgeRequests;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addBadgeRequests();

        Bus::fake([SendPermissionRequestReviewedNotificationsJob::class]);

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($this->admin);
    }

    public function test_handle_makes_trooper_an_event_coordinator(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::EVENT_COORDINATOR)
            ->create();

        (new ApproveTrooperPermissions($this->admin, $permission_request))->handle();

        $this->assertSame(MembershipRole::EVENT_COORDINATOR, $trooper->fresh()->membership_role);
        $this->assertSame(PermissionRequestStatus::APPROVED, $permission_request->fresh()->status);
    }

    public function test_handle_clears_moderation_when_a_moderator_becomes_an_event_coordinator(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperAssignment::IS_MODERATOR => true]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::EVENT_COORDINATOR)
            ->create();

        (new ApproveTrooperPermissions($this->admin, $permission_request))->handle();

        $this->assertSame(MembershipRole::EVENT_COORDINATOR, $trooper->fresh()->membership_role);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
    }

    public function test_handle_sets_an_event_coordinator_back_to_member(): void
    {
        $trooper = Trooper::factory()->asEventCoordinator()->create();

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MEMBER)
            ->create();

        (new ApproveTrooperPermissions($this->admin, $permission_request))->handle();

        $this->assertSame(MembershipRole::MEMBER, $trooper->fresh()->membership_role);
    }

    public function test_handle_makes_trooper_moderator_of_requested_organizations(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR, [$organization->id])
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        $this->assertSame(MembershipRole::MODERATOR, $trooper->fresh()->membership_role);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $permission_request->refresh();
        $this->assertSame(PermissionRequestStatus::APPROVED, $permission_request->status);
        $this->assertSame($this->admin->id, $permission_request->reviewer_trooper_id);
        $this->assertNotNull($permission_request->reviewed_at);
        Bus::assertDispatched(SendPermissionRequestReviewedNotificationsJob::class);
    }

    public function test_handle_moves_moderation_and_keeps_membership(): void
    {
        $rebel_legion = Organization::factory()->asOrganization()->create();
        $cfl = Organization::factory()->asRegion()->withParent($rebel_legion)->create();
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($rebel_legion)->create([
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR, [$cfl->id])
            ->create([PermissionRequest::MODERATED_ORGANIZATION_IDS_TO_REMOVE => [$rebel_legion->id]]);

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ORGANIZATION_ID => $rebel_legion->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ORGANIZATION_ID => $cfl->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);
    }

    public function test_handle_restores_deleted_assignment_as_moderator_only(): void
    {
        $rebel_legion = Organization::factory()->asOrganization()->create();
        $cfl = Organization::factory()->asRegion()->withParent($rebel_legion)->create();
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($cfl)->create([
            TrooperAssignment::IS_MEMBER => true,
        ]);
        $old = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($rebel_legion)
            ->create([TrooperAssignment::IS_MODERATOR => true]);
        $old->delete();
        // Set after deletion so the observer's one-membership rule isn't hit while seeding.
        TrooperAssignment::withTrashed()
            ->whereKey($old->id)
            ->update([TrooperAssignment::IS_MEMBER => true]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR, [$rebel_legion->id])
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ID => $old->id,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::IS_MODERATOR => true,
            TrooperAssignment::DELETED_AT => null,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ORGANIZATION_ID => $cfl->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_handle_keeps_existing_member_assignment_when_granting_moderator(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([
                TrooperAssignment::IS_MEMBER => true,
            ]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR, [$organization->id])
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        $this->assertDatabaseCount('tt_trooper_assignments', 1);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => true,
        ]);
    }

    public function test_handle_sets_moderator_back_to_regular_trooper(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([
                TrooperAssignment::IS_MODERATOR => true,
            ]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MEMBER)
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        $this->assertSame(MembershipRole::MEMBER, $trooper->fresh()->membership_role);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
    }

    public function test_handle_applies_plugin_extensions(): void
    {
        $permission_request = PermissionRequest::factory()
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->withRole(null)
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        $this->assertSame([$permission_request->id], BadgeRequests::$approved);
        $this->assertSame(PermissionRequestStatus::APPROVED, $permission_request->fresh()->status);
    }

    public function test_handle_rolls_back_role_change_when_an_extension_fails(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        BadgeRequests::$approval_failure = 'The badge office is closed.';

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR)
            ->create();

        try
        {
            //  dispatching through the bus wraps the approval in its transaction
            ApproveTrooperPermissions::call(permission_request: $permission_request);
            $this->fail('Expected the extension failure to be thrown.');
        }
        catch (PermissionApprovalException $e)
        {
            $this->assertSame('The badge office is closed.', $e->getMessage());
        }

        $this->assertSame(MembershipRole::MEMBER, $trooper->fresh()->membership_role);
        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
    }

    public function test_handle_refuses_to_change_an_administrator(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MEMBER)
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);

        $this->expectException(RuntimeException::class);

        $subject->handle();
    }

    public function test_handle_rejects_request_that_is_not_pending(): void
    {
        $permission_request = PermissionRequest::factory()->asDenied()->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);

        $this->expectException(RuntimeException::class);

        $subject->handle();
    }

    public function test_handle_fires_trooper_permissions_approved(): void
    {
        EventFacade::fake([TrooperPermissionsApproved::class]);
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $permission_request = PermissionRequest::factory()
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->withRole(MembershipRole::MODERATOR, [$organization->id])
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        EventFacade::assertDispatched(TrooperPermissionsApproved::class, fn (TrooperPermissionsApproved $approved): bool => $approved->permission_request->is($permission_request));
    }
}
