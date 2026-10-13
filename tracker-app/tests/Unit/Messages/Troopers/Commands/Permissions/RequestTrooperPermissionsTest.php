<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Messages\Troopers\Commands\Permissions\RequestTrooperPermissions;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RequestTrooperPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_records_request_and_notifies_admins(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $subject = new RequestTrooperPermissions(
            actor: $moderator,
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            moderated_organization_ids: ['3', 3, '4'],
            note: 'Running the Florida events',
        );
        $result = $subject->handle();

        $this->assertSame($trooper->id, $result->trooper_id);
        $this->assertSame($moderator->id, $result->requester_trooper_id);
        $this->assertSame(MembershipRole::MODERATOR, $result->membership_role);
        $this->assertSame([3, 4], $result->moderated_organization_ids);
        $this->assertSame('Running the Florida events', $result->note);
        $this->assertSame(PermissionRequestStatus::PENDING, $result->status);

        Bus::assertDispatched(SendPermissionRequestNotificationsJob::class);
    }

    public function test_handle_drops_organizations_covered_by_a_picked_parent(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $parent = Organization::factory()->asOrganization()->create();
        $child = Organization::factory()->asRegion()->withParent($parent)->create();
        $other = Organization::factory()->asOrganization()->create();

        $subject = new RequestTrooperPermissions(
            actor: Trooper::factory()->asAdministrator()->create(),
            trooper: Trooper::factory()->asMember()->create(),
            membership_role: MembershipRole::MODERATOR,
            moderated_organization_ids: [$child->id, $parent->id, $other->id],
        );
        $result = $subject->handle();

        $this->assertSame([$parent->id, $other->id], $result->moderated_organization_ids);
    }

    public function test_handle_records_moderation_change(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $rebel_legion = Organization::factory()->asOrganization()->create();
        $cfl = Organization::factory()->asRegion()->withParent($rebel_legion)->create();
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($rebel_legion)->create([
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $subject = new RequestTrooperPermissions(
            actor: Trooper::factory()->asAdministrator()->create(),
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            moderated_organization_ids: [$cfl->id],
        );
        $result = $subject->handle();

        $this->assertSame([$cfl->id], $result->moderated_organization_ids);
        $this->assertSame([$rebel_legion->id], $result->unmoderated_organization_ids);
    }

    public function test_handle_keeps_moderation_outside_requester_scope(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $mine = Organization::factory()->asOrganization()->create();
        $theirs = Organization::factory()->asOrganization()->create();
        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($mine)->create([
            TrooperAssignment::IS_MODERATOR => true,
        ]);
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($theirs)->create([
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $subject = new RequestTrooperPermissions(
            actor: $moderator,
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            moderated_organization_ids: [$mine->id],
        );
        $result = $subject->handle();

        $this->assertSame([$mine->id], $result->moderated_organization_ids);
        $this->assertSame([], $result->unmoderated_organization_ids);
    }

    public function test_handle_drops_organizations_when_not_requesting_moderator(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $subject = new RequestTrooperPermissions(
            actor: Trooper::factory()->asModerator()->create(),
            trooper: Trooper::factory()->asModerator()->create(),
            membership_role: MembershipRole::MEMBER,
            moderated_organization_ids: [3],
        );
        $result = $subject->handle();

        $this->assertSame([], $result->moderated_organization_ids);
    }

    public function test_handle_snapshots_forum_group_titles(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 9, 'title' => 'Recruiters'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                ],
            ], 200),
        ]);

        $subject = new RequestTrooperPermissions(
            actor: Trooper::factory()->asModerator()->create(),
            trooper: Trooper::factory()->asMember()->create(),
            forum_group_ids_to_add: ['20'],
            forum_group_ids_to_remove: [9],
        );
        $result = $subject->handle();

        $event_coordinators = [['id' => 20, 'title' => 'Event Coordinators']];

        $this->assertNull($result->membership_role);
        $this->assertSame($event_coordinators, $result->forum_groups_to_add);
        $this->assertSame([['id' => 9, 'title' => 'Recruiters']], $result->forum_groups_to_remove);
    }
}
