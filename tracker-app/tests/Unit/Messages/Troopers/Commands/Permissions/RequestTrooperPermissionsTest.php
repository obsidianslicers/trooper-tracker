<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Permissions;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Messages\Troopers\Commands\Permissions\RequestTrooperPermissions;
use App\Models\Trooper;
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

        $this->actingAs($moderator);

        $result = RequestTrooperPermissions::call(
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            moderated_organization_ids: ['3', 3, '4'],
            note: 'Running the Florida events',
        );

        $this->assertSame($trooper->id, $result->trooper_id);
        $this->assertSame($moderator->id, $result->requester_trooper_id);
        $this->assertSame(MembershipRole::MODERATOR, $result->membership_role);
        $this->assertSame([3, 4], $result->moderated_organization_ids);
        $this->assertSame('Running the Florida events', $result->note);
        $this->assertSame(PermissionRequestStatus::PENDING, $result->status);

        Bus::assertDispatched(SendPermissionRequestNotificationsJob::class);
    }

    public function test_handle_drops_organizations_when_not_requesting_moderator(): void
    {
        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $this->actingAs(Trooper::factory()->asModerator()->create());

        $result = RequestTrooperPermissions::call(
            trooper: Trooper::factory()->asModerator()->create(),
            membership_role: MembershipRole::MEMBER,
            moderated_organization_ids: [3],
        );

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

        $this->actingAs(Trooper::factory()->asModerator()->create());

        $result = RequestTrooperPermissions::call(
            trooper: Trooper::factory()->asMember()->create(),
            forum_group_ids_to_add: ['20'],
            forum_group_ids_to_remove: [9],
        );

        $this->assertNull($result->membership_role);
        $this->assertSame([['id' => 20, 'title' => 'Event Coordinators']], $result->forum_groups_to_add);
        $this->assertSame([['id' => 9, 'title' => 'Recruiters']], $result->forum_groups_to_remove);
    }
}
