<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Forums;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Forums\XenforoService;
use App\Services\Forums\XenforoUserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class XenforoUserSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $xenforo;

    private XenforoUserSyncService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xenforo = Mockery::mock(XenforoService::class);
        $this->subject = new XenforoUserSyncService($this->xenforo);
    }

    public function test_sync_trooper_skips_when_no_xenforo_account(): void
    {
        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value]);

        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')
            ->once()
            ->with($trooper->id)
            ->andReturn(null);

        $this->xenforo->shouldNotReceive('update_user');

        $this->subject->syncTrooper($trooper);
    }

    public function test_sync_trooper_uses_retired_group_when_global_status_is_retired(): void
    {
        $org = Organization::factory()->create([
            Organization::XENFORO_GROUP_ACTIVE_ID => 10,
            Organization::XENFORO_GROUP_RETIRED_ID => 20,
        ]);
        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value]);
        $trooper->organizations()->attach($org->id, ['membership_status' => 'active']);

        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')
            ->once()
            ->andReturn(999);

        $this->xenforo->shouldReceive('get_user')->andReturn([
            'status' => 200,
            'body' => ['user' => ['secondary_group_ids' => [10, 99]]],
        ]);
        $this->xenforo->shouldReceive('update_user')
            ->once()
            ->with(999, Mockery::on(function (array $payload) {
                $sent = $payload['secondary_group_ids'] ?? null;

                return is_array($sent)
                    && ! in_array(10, $sent, true)  // active TT group removed
                    && in_array(20, $sent, true)    // retired TT group added
                    && in_array(99, $sent, true);   // external group preserved
            }))
            ->andReturn(['status' => 200, 'body' => []]);

        $this->subject->syncTrooper($trooper);
    }

    public function test_sync_trooper_does_not_send_secondary_group_ids_when_api_fails(): void
    {
        $org = Organization::factory()->create([Organization::XENFORO_GROUP_ACTIVE_ID => 10]);
        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value]);
        $trooper->organizations()->attach($org->id, ['membership_status' => 'active']);

        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(999);
        $this->xenforo->shouldReceive('get_user')->andReturn(['status' => 500, 'body' => null]);
        $this->xenforo->shouldReceive('update_user')
            ->once()
            ->with(999, Mockery::on(function (array $payload) {
                return ! array_key_exists('secondary_group_ids', $payload);
            }))
            ->andReturn(['status' => 200, 'body' => []]);

        $this->subject->syncTrooper($trooper);
    }

    public function test_sync_trooper_does_not_send_secondary_group_ids_when_no_change(): void
    {
        $org = Organization::factory()->create([Organization::XENFORO_GROUP_ACTIVE_ID => 10]);
        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value]);
        $trooper->organizations()->attach($org->id, ['membership_status' => 'active']);

        // XenForo already has the TT-managed group — no change needed.
        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(999);
        $this->xenforo->shouldReceive('get_user')->andReturn([
            'status' => 200,
            'body' => ['user' => ['secondary_group_ids' => [10]]],
        ]);
        $this->xenforo->shouldReceive('update_user')
            ->once()
            ->with(999, Mockery::on(function (array $payload) {
                return ! array_key_exists('secondary_group_ids', $payload);
            }))
            ->andReturn(['status' => 200, 'body' => []]);

        $this->subject->syncTrooper($trooper);
    }

    public function test_sync_trooper_preserves_external_groups_when_change_needed(): void
    {
        $org = Organization::factory()->create([Organization::XENFORO_GROUP_ACTIVE_ID => 10]);
        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value]);
        $trooper->organizations()->attach($org->id, ['membership_status' => 'active']);

        // XenForo has external group 99 (not TT-managed) and no TT group.
        // TT wants group 10. Change IS needed — group 99 must be preserved.
        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(999);
        $this->xenforo->shouldReceive('get_user')->andReturn([
            'status' => 200,
            'body' => ['user' => ['secondary_group_ids' => [99]]],
        ]);
        $this->xenforo->shouldReceive('update_user')
            ->once()
            ->with(999, Mockery::on(function (array $payload) {
                $sent = $payload['secondary_group_ids'] ?? null;

                return is_array($sent)
                    && in_array(99, $sent, true)   // external group preserved
                    && in_array(10, $sent, true);  // TT group added
            }))
            ->andReturn(['status' => 200, 'body' => []]);

        $this->subject->syncTrooper($trooper);
    }

    public function test_desired_group_ids_includes_all_ancestor_groups(): void
    {
        // Three-level hierarchy: club → garrison → squad
        $club = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::XENFORO_GROUP_ACTIVE_ID => 100,
        ]);
        $garrison = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
            Organization::PARENT_ID => $club->id,
            Organization::XENFORO_GROUP_ACTIVE_ID => 200,
        ]);
        $squad = Organization::factory()->create([
            Organization::TYPE => OrganizationType::UNIT,
            Organization::PARENT_ID => $garrison->id,
            Organization::XENFORO_GROUP_ACTIVE_ID => 300,
        ]);

        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value]);
        $trooper->organizations()->attach($squad->id, ['membership_status' => 'active']);

        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(999);
        $this->xenforo->shouldReceive('get_user')->andReturn([
            'status' => 200,
            'body' => ['user' => ['secondary_group_ids' => []]],
        ]);

        $debug = $this->subject->debugSync($trooper);

        $this->assertContains(300, $debug['desired_managed'], 'Squad group missing');
        $this->assertContains(200, $debug['desired_managed'], 'Garrison group missing');
        $this->assertContains(100, $debug['desired_managed'], 'Club group missing');
    }

    public function test_retired_global_status_removes_active_assignment_groups(): void
    {
        $club = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::XENFORO_GROUP_ACTIVE_ID => 100,
            Organization::XENFORO_GROUP_RETIRED_ID => 200,
        ]);
        $unit = Organization::factory()->create([
            Organization::TYPE => OrganizationType::UNIT,
            Organization::PARENT_ID => $club->id,
            Organization::XENFORO_GROUP_ACTIVE_ID => 300,
        ]);

        $trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value]);
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($unit)
            ->asMember()
            ->create();

        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(999);
        $this->xenforo->shouldReceive('get_user')->andReturn([
            'status' => 200,
            'body' => ['user' => ['secondary_group_ids' => [100, 300]]],
        ]);

        $debug = $this->subject->debugSync($trooper);

        $this->assertContains(200, $debug['desired_managed'], 'Retired club group missing');
        $this->assertNotContains(100, $debug['desired_managed'], 'Active club group should be removed');
        $this->assertNotContains(300, $debug['desired_managed'], 'Active unit group should be removed');
    }

    public function test_debug_sync_returns_null_xenforo_user_id_when_unlinked(): void
    {
        $trooper = Trooper::factory()->create();

        $this->xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(null);

        $debug = $this->subject->debugSync($trooper);

        $this->assertNull($debug['xenforo_user_id']);
        $this->assertFalse($debug['would_send']);
    }
}
