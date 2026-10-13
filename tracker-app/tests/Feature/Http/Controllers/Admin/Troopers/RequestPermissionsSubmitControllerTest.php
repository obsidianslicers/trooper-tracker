<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\OauthProvider;
use App\Jobs\SendPermissionRequestNotificationsJob;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RequestPermissionsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake([SendPermissionRequestNotificationsJob::class]);

        $this->moderator = Trooper::factory()->asModerator()->create();
        $this->organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->create();
        TrooperAssignment::factory()
            ->forTrooper($this->moderator)
            ->forOrganization($this->organization)
            ->create([TrooperAssignment::IS_MODERATOR => true]);
    }

    public function test_invoke_submits_moderator_request(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->submit([
            'trooper_id' => $trooper->id,
            'membership_role' => 'moderator',
            'moderated_organization_ids' => [$this->organization->id],
            'note' => 'Handles the Tampa events',
        ]);

        $response->assertRedirect(route('admin.troopers.permissions.request'));
        $response->assertSessionHasNoErrors();

        $permission_request = PermissionRequest::sole();
        $this->assertSame($trooper->id, $permission_request->trooper_id);
        $this->assertSame($this->moderator->id, $permission_request->requester_trooper_id);
        $this->assertSame(MembershipRole::MODERATOR, $permission_request->membership_role);
        $this->assertSame(
            [$this->organization->id],
            $permission_request->moderated_organization_ids
        );
        $this->assertSame('Handles the Tampa events', $permission_request->note);
        Bus::assertDispatched(SendPermissionRequestNotificationsJob::class);
    }

    public function test_invoke_rejects_organization_the_moderator_does_not_moderate(): void
    {
        $other = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        $response = $this->submit([
            'trooper_id' => Trooper::factory()->asMember()->create()->id,
            'membership_role' => 'moderator',
            'moderated_organization_ids' => [$other->id],
        ]);

        $response->assertSessionHasErrors('moderated_organization_ids.0');
        $this->assertDatabaseCount('tt_permission_requests', 0);
    }

    public function test_invoke_rejects_unchanged_moderation(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($this->organization)
            ->create([TrooperAssignment::IS_MODERATOR => true]);

        $response = $this->submit([
            'trooper_id' => $trooper->id,
            'membership_role' => 'moderator',
            'moderated_organization_ids' => [$this->organization->id],
        ]);

        $response->assertSessionHasErrors([
            'moderated_organization_ids' => 'Change at least one organization.',
        ]);
        $this->assertDatabaseCount('tt_permission_requests', 0);
    }

    public function test_invoke_requires_something_to_be_requested(): void
    {
        $response = $this->submit([
            'trooper_id' => Trooper::factory()->asMember()->create()->id,
            'membership_role' => '',
        ]);

        $response->assertSessionHasErrors('membership_role');
    }

    public function test_invoke_rejects_request_for_yourself(): void
    {
        $response = $this->submit([
            'trooper_id' => $this->moderator->id,
            'membership_role' => 'member',
        ]);

        $response->assertSessionHasErrors('trooper_id');
    }

    public function test_invoke_rejects_role_change_for_administrator(): void
    {
        $response = $this->submit([
            'trooper_id' => Trooper::factory()->asAdministrator()->create()->id,
            'membership_role' => 'member',
        ]);

        $response->assertSessionHasErrors('membership_role');
    }

    public function test_invoke_rejects_demoting_a_trooper_who_is_not_a_moderator(): void
    {
        $response = $this->submit([
            'trooper_id' => Trooper::factory()->asMember()->create()->id,
            'membership_role' => 'member',
        ]);

        $response->assertSessionHasErrors('membership_role');
    }

    public function test_invoke_submits_forum_group_request(): void
    {
        $this->configureXenforo();
        $trooper = Trooper::factory()->asMember()->create();
        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '77',
        ]);

        $response = $this->submit([
            'trooper_id' => $trooper->id,
            'forum_group_ids_to_add' => [20],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(
            [['id' => 20, 'title' => 'Event Coordinators']],
            PermissionRequest::sole()->forum_groups_to_add
        );
    }

    public function test_invoke_rejects_forum_groups_for_trooper_without_forum_account(): void
    {
        $this->configureXenforo();

        $response = $this->submit([
            'trooper_id' => Trooper::factory()->asMember()->create()->id,
            'forum_group_ids_to_add' => [20],
        ]);

        $response->assertSessionHasErrors('forum_group_ids_to_add');
    }

    public function test_invoke_rejects_unknown_forum_group(): void
    {
        $this->configureXenforo();

        $response = $this->submit([
            'trooper_id' => Trooper::factory()->asMember()->create()->id,
            'forum_group_ids_to_add' => [999],
        ]);

        $response->assertSessionHasErrors('forum_group_ids_to_add.0');
    }

    public function test_invoke_rejects_malformed_input_without_crashing(): void
    {
        $response = $this->submit([
            'trooper_id' => ['1'],
            'membership_role' => 'moderator',
            'moderated_organization_ids' => 'all',
            'forum_group_ids_to_add' => 'abc',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'trooper_id',
            'moderated_organization_ids',
            'forum_group_ids_to_add',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function submit(array $data): TestResponse
    {
        return $this->actingAs($this->moderator)
            ->post(route('admin.troopers.permissions.request'), $data);
    }

    private function configureXenforo(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        Http::fake([
            'https://xf.test/index.php?api/user-groups' => Http::response([
                'userGroups' => [['groupID' => 20, 'title' => 'Event Coordinators']],
            ], 200),
        ]);
    }
}
