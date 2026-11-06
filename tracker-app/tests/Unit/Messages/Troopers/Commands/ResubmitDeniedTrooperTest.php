<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Jobs\SendTrooperResubmittedNotificationsJob;
use App\Messages\Troopers\Commands\ResubmitDeniedTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ResubmitDeniedTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_membership_status_to_pending(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        (new ResubmitDeniedTrooper($trooper, []))->handle();

        $this->assertEquals(MembershipStatus::PENDING, $trooper->fresh()->membership_status);
    }

    public function test_handle_deletes_denied_trooper_requests(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->asDenied()
            ->count(2)
            ->create();

        (new ResubmitDeniedTrooper($trooper, []))->handle();

        $this->assertEquals(0, TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)->denied()->count());
    }

    public function test_handle_creates_trooper_request_for_member_with_selected_organization(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $org = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($org)->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => [
                'selected' => '1',
                'region_id' => $region->id,
            ],
        ]))->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $region->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $org->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
    }

    public function test_handle_creates_trooper_request_with_unit_for_member(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $org = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($org)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => [
                'selected' => '1',
                'region_id' => $region->id,
                'unit_id' => $unit->id,
            ],
        ]))->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $org->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
    }

    public function test_handle_creates_trooper_request_for_visitor_without_region(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);

        $org = Organization::factory()->asOrganization()->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => ['selected' => '1'],
        ]))->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $org->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $org->id,
        ]);
    }

    public function test_handle_skips_unselected_organizations(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $org = Organization::factory()->asOrganization()->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => ['selected' => ''],
        ]))->handle();

        $this->assertDatabaseMissing('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
    }

    public function test_handle_skips_trooper_requests_for_handler_role(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER,
        ]);

        $org = Organization::factory()->asOrganization()->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => ['selected' => '1'],
        ]))->handle();

        $this->assertDatabaseMissing('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_handle_skips_organization_if_region_not_found(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $org = Organization::factory()->asOrganization()->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => [
                'selected' => '1',
                'region_id' => 99999,
            ],
        ]))->handle();

        $this->assertDatabaseMissing('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_handle_stores_identifier_on_trooper_request(): void
    {
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $org = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($org)->create();

        (new ResubmitDeniedTrooper($trooper, [
            $org->id => [
                'selected' => '1',
                'region_id' => $region->id,
                'identifier' => 'TK-12345',
            ],
        ]))->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::IDENTIFIER => 'TK-12345',
        ]);
    }

    public function test_handle_dispatches_send_trooper_resubmitted_notifications_job(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asDenied()->create();

        (new ResubmitDeniedTrooper($trooper, []))->handle();

        Queue::assertPushed(SendTrooperResubmittedNotificationsJob::class);
    }

    public function test_handle_skips_unknown_organizations_and_incomplete_selections(): void
    {
        Queue::fake();
        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);
        $org = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($org)->create();
        Organization::factory()->asUnit()->withParent($region)->create();
        $other_org = Organization::factory()->asOrganization()->create();

        (new ResubmitDeniedTrooper($trooper, [
            999999 => ['selected' => '1', 'region_id' => $region->id],
            $org->id => ['selected' => '1', 'region_id' => $region->id],
            $other_org->id => ['selected' => '1'],
        ]))->handle();

        $this->assertSame(0, TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)->count());
        Queue::assertPushed(SendTrooperResubmittedNotificationsJob::class);
    }
}
