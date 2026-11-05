<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Exceptions\PermissionApprovalException;
use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Jobs\SendPermissionRequestReviewedNotificationsJob;
use App\Messages\Troopers\Commands\Permissions\ApproveTrooperPermissions;
use App\Messages\Troopers\Resources\PermissionRequestsCollection;
use App\Models\OauthLogin;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;
use TroopTracker\Plugins\Xenforo\Models\PermissionRequestGroups;

class ForumGroupRequestsTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    private const EVENT_COORDINATORS = ['id' => 20, 'title' => 'Event Coordinators'];

    private const RECRUITERS = ['id' => 9, 'title' => 'Recruiters'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
            'plugins.xenforo.api_user' => '1',
        ]);
        Bus::fake([
            SendPermissionRequestNotificationsJob::class,
            SendPermissionRequestReviewedNotificationsJob::class,
        ]);
    }

    public function test_lookup_shows_addable_and_current_groups(): void
    {
        $trooper = $this->linkedTrooper('77');
        $this->fakeForum(current_ids: [9]);

        $response = $this->actingAs(Trooper::factory()->asModerator()->create())
            ->getJson(route('admin.troopers.permissions.lookup', $trooper))
            ->assertOk();

        $html = $response->json('extension_sections.0.html');
        $this->assertSame('xenforo', $response->json('extension_sections.0.key'));
        $this->assertMatchesRegularExpression(
            '/name="extensions\[xenforo\]\[groups_to_add\]\[\]"\s+value="20"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/name="extensions\[xenforo\]\[groups_to_remove\]\[\]"\s+value="9"/',
            $html
        );
    }

    public function test_lookup_says_when_the_trooper_has_no_forum_account(): void
    {
        $this->fakeForum();

        $html = $this->actingAs(Trooper::factory()->asModerator()->create())
            ->getJson(route('admin.troopers.permissions.lookup', Trooper::factory()->asMember()->create()))
            ->json('extension_sections.0.html');

        $this->assertStringContainsString('no linked forum account', $html);
    }

    public function test_lookup_has_no_section_while_the_forum_is_not_configured(): void
    {
        config(['plugins.xenforo.api_key' => null]);

        $this->actingAs(Trooper::factory()->asModerator()->create())
            ->getJson(route('admin.troopers.permissions.lookup', Trooper::factory()->asMember()->create()))
            ->assertJson(['extension_sections' => []]);
    }

    public function test_submitting_stores_the_group_titles(): void
    {
        $trooper = $this->linkedTrooper('77');
        $this->fakeForum();

        $this->submit($trooper, ['groups_to_add' => ['20'], 'groups_to_remove' => [9]])
            ->assertSessionHasNoErrors();

        $groups = PermissionRequestGroups::forRequest(PermissionRequest::sole()->id);
        $this->assertSame([self::EVENT_COORDINATORS], $groups->groups_to_add);
        $this->assertSame([self::RECRUITERS], $groups->groups_to_remove);
        $this->assertContains(
            'Add Forum Groups: Event Coordinators',
            PermissionRequestsCollection::changeSummary(PermissionRequest::sole()),
        );
    }

    public function test_submitting_rejects_an_unknown_group(): void
    {
        $this->fakeForum();

        $this->submit($this->linkedTrooper('77'), ['groups_to_add' => [999]])
            ->assertSessionHasErrors('extensions.xenforo.groups_to_add.0');
    }

    public function test_submitting_rejects_a_trooper_without_a_forum_account(): void
    {
        $this->fakeForum();

        $this->submit(Trooper::factory()->asMember()->create(), ['groups_to_add' => [20]])
            ->assertSessionHasErrors(['extensions.xenforo.groups_to_add' => 'This trooper has no forum account.']);
    }

    public function test_submitting_rejects_adding_and_removing_the_same_group(): void
    {
        $this->fakeForum();

        $this->submit($this->linkedTrooper('77'), ['groups_to_add' => [20], 'groups_to_remove' => [20]])
            ->assertSessionHasErrors('extensions.xenforo.groups_to_add');
    }

    public function test_approving_updates_forum_groups_keeping_unrelated_groups(): void
    {
        $trooper = $this->linkedTrooper('77');
        Http::fake([
            'https://xf.test/api/users/77' => Http::sequence()
                ->push(['user' => ['secondary_group_ids' => [5, 9, 12]]], 200)
                ->push(['success' => true], 200),
        ]);
        $permission_request = $this->requestWithGroups($trooper, null, [self::EVENT_COORDINATORS], [self::RECRUITERS]);

        ApproveTrooperPermissions::call(permission_request: $permission_request);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://xf.test/api/users/77'
            && $request['secondary_group_ids'] == [5, 12, 20]);
        $this->assertSame(PermissionRequestStatus::APPROVED, $permission_request->fresh()->status);
    }

    public function test_approving_rolls_back_the_role_change_when_the_forum_fails(): void
    {
        $trooper = $this->linkedTrooper('77');
        Http::fake([
            'https://xf.test/api/users/77' => Http::sequence()
                ->push(['user' => ['secondary_group_ids' => []]], 200)
                ->push(['errors' => []], 500),
        ]);
        $permission_request = $this->requestWithGroups($trooper, MembershipRole::MODERATOR, [self::EVENT_COORDINATORS]);

        try
        {
            ApproveTrooperPermissions::call(permission_request: $permission_request);
            $this->fail('Expected the forum failure to be thrown.');
        }
        catch (PermissionApprovalException)
        {
        }

        $this->assertSame(MembershipRole::MEMBER, $trooper->fresh()->membership_role);
        $this->assertSame(PermissionRequestStatus::PENDING, $permission_request->fresh()->status);
    }

    public function test_approving_fails_when_the_trooper_has_no_forum_account(): void
    {
        $permission_request = $this->requestWithGroups(
            Trooper::factory()->asMember()->create(),
            null,
            [self::EVENT_COORDINATORS],
        );

        $this->expectException(PermissionApprovalException::class);

        ApproveTrooperPermissions::call(permission_request: $permission_request);
    }

    public function test_enabling_copies_groups_from_the_old_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('tt_permission_requests', ['forum_groups_to_add', 'forum_groups_to_remove']));
        $permission_request = PermissionRequest::factory()->create();
        DB::table('tt_permission_requests')->where('id', $permission_request->id)->update([
            'forum_groups_to_add' => json_encode([self::EVENT_COORDINATORS]),
            'forum_groups_to_remove' => json_encode([]),
        ]);
        PermissionRequest::factory()->create();
        Schema::drop('tt_xenforo_permission_request_groups');

        $migration = require base_path(
            'plugins/xenforo/database/migrations/2026_11_05_000003_create_xenforo_permission_request_groups_table.php'
        );
        $migration->up();

        $this->assertSame(1, PermissionRequestGroups::count());
        $this->assertSame(
            [self::EVENT_COORDINATORS],
            PermissionRequestGroups::forRequest($permission_request->id)->groups_to_add,
        );
    }

    /**
     * @param  array<string, mixed>  $groups
     */
    private function submit(Trooper $trooper, array $groups): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs(Trooper::factory()->asModerator()->create())
            ->post(route('admin.troopers.permissions.request'), [
                'trooper_id' => $trooper->id,
                'extensions' => ['xenforo' => $groups],
            ]);
    }

    /**
     * @param  array<int, array{id:int,title:string}>  $add
     * @param  array<int, array{id:int,title:string}>  $remove
     */
    private function requestWithGroups(
        Trooper $trooper,
        ?MembershipRole $role,
        array $add = [],
        array $remove = [],
    ): PermissionRequest {
        $this->actingAs(Trooper::factory()->asAdministrator()->create());

        $permission_request = PermissionRequest::factory()->forTrooper($trooper)->withRole($role)->create();

        PermissionRequestGroups::create([
            PermissionRequestGroups::PERMISSION_REQUEST_ID => $permission_request->id,
            PermissionRequestGroups::GROUPS_TO_ADD => $add,
            PermissionRequestGroups::GROUPS_TO_REMOVE => $remove,
        ]);

        return $permission_request;
    }

    private function linkedTrooper(string $forum_user_id): Trooper
    {
        $trooper = Trooper::factory()->asMember()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => $forum_user_id,
        ]);

        return $trooper;
    }

    /**
     * @param  array<int, int>  $current_ids
     */
    private function fakeForum(array $current_ids = []): void
    {
        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [
                    ['groupID' => 9, 'title' => 'Recruiters'],
                    ['groupID' => 20, 'title' => 'Event Coordinators'],
                ],
            ], 200),
            'https://xf.test/api/users/77' => Http::response(
                ['user' => ['secondary_group_ids' => $current_ids]],
                200
            ),
            '*' => Http::response([], 404),
        ]);
    }
}
