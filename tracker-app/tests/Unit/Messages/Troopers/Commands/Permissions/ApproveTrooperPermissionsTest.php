<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\OauthProvider;
use App\Enums\PermissionRequestStatus;
use App\Exceptions\ForumGroupUpdateException;
use App\Messages\Troopers\Commands\Permissions\ApproveTrooperPermissions;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ApproveTrooperPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
            'services.xenforo.api_user' => '1',
        ]);

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($this->admin);
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

    public function test_handle_updates_forum_groups_keeping_unrelated_groups(): void
    {
        $trooper = $this->createLinkedTrooper('77');

        Http::fake([
            'https://xf.test/api/users/77' => Http::sequence()
                ->push(['user' => ['secondary_group_ids' => [5, 9, 12]]], 200)
                ->push(['success' => true], 200),
        ]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(null)
            ->withForumGroups(
                add: [['id' => 20, 'title' => 'Event Coordinators']],
                remove: [['id' => 9, 'title' => 'Recruiters']],
            )
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);
        $subject->handle();

        Http::assertSent(function ($request): bool
        {
            return $request->method() === 'POST'
                && $request->url() === 'https://xf.test/api/users/77'
                && $request['secondary_group_ids'] == [5, 12, 20];
        });

        $this->assertSame(PermissionRequestStatus::APPROVED, $permission_request->fresh()->status);
    }

    public function test_handle_rolls_back_role_change_when_forum_update_fails(): void
    {
        $trooper = $this->createLinkedTrooper('77');

        Http::fake([
            'https://xf.test/api/users/77' => Http::sequence()
                ->push(['user' => ['secondary_group_ids' => []]], 200)
                ->push(['errors' => []], 500),
        ]);

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(MembershipRole::MODERATOR)
            ->withForumGroups(add: [['id' => 20, 'title' => 'Event Coordinators']])
            ->create();

        try
        {
            //  dispatching through the bus wraps the approval in its transaction
            ApproveTrooperPermissions::call(permission_request: $permission_request);
            $this->fail('Expected the forum failure to be thrown.');
        }
        catch (ForumGroupUpdateException)
        {
        }

        $this->assertSame(MembershipRole::MEMBER, $trooper->fresh()->membership_role);
        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
    }

    public function test_handle_throws_when_trooper_has_no_forum_account(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $permission_request = PermissionRequest::factory()
            ->forTrooper($trooper)
            ->withRole(null)
            ->withForumGroups(add: [['id' => 20, 'title' => 'Event Coordinators']])
            ->create();

        $subject = new ApproveTrooperPermissions($this->admin, $permission_request);

        $this->expectException(ForumGroupUpdateException::class);

        $subject->handle();
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

    private function createLinkedTrooper(string $xenforo_user_id): Trooper
    {
        $trooper = Trooper::factory()->asMember()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => $xenforo_user_id,
        ]);

        return $trooper;
    }
}
