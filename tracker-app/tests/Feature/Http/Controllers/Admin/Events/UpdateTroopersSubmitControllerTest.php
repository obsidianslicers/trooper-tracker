<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_statuses_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [],
        ]);

        $response->assertRedirect(route('admin.events.troopers', ['event' => $event->id]));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/'.$event->id.'/troopers', []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_saves_submitted_org_selection_when_costume_set(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$org1->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$org1->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_saves_submitted_station_selection(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'event_shift_station_id' => $station->id,
                ],
            ],
        ]);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $station->id,
        ]);
    }

    public function test_invoke_moves_trooper_to_standby_when_selected_station_is_full(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()
            ->forEventShift($event_shift)
            ->withTroopersAllowed(1)
            ->create();
        EventTrooper::factory()->forEventShiftStation($station)->asGoing()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([EventTrooper::STATUS => EventTrooperStatus::STAND_BY]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => EventTrooperStatus::GOING->value,
                    'event_shift_station_id' => $station->id,
                ],
            ],
        ]);

        $this->assertSame(EventTrooperStatus::STAND_BY, $event_trooper->fresh()->status);
        $this->assertSame($station->id, $event_trooper->fresh()->event_shift_station_id);
    }

    public function test_invoke_does_not_clear_station_for_stationed_shift(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->asGoing()
            ->create();

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => EventTrooperStatus::GOING->value,
                    'event_shift_station_id' => '',
                ],
            ],
        ]);

        $this->assertSame($station->id, $event_trooper->fresh()->event_shift_station_id);
    }

    public function test_invoke_ignores_station_selection_from_another_shift(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $other_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();
        $other_station = EventShiftStation::factory()->forEventShift($other_shift)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'event_shift_station_id' => $other_station->id,
                ],
            ],
        ]);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $station->id,
        ]);
    }

    public function test_invoke_saves_regular_costume_org_selection_without_trooper_organization_rows(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org1->update([Organization::NODE_PATH => (string) $org1->id]);
        $org2->update([Organization::NODE_PATH => (string) $org2->id]);
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => $costume->id]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$org1->id, $org2->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_maps_selected_parent_org_to_approved_child_credit_without_trooper_organization_rows(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $parent_org = Organization::factory()->create();
        $child_org = Organization::factory()->withParent($parent_org)->create();
        \Illuminate\Support\Facades\DB::table('tt_organizations')
            ->where('id', $child_org->id)
            ->update([
                Organization::PARENT_ID => $parent_org->id,
                Organization::NODE_PATH => $parent_org->id.':'.$child_org->id.':',
            ]);
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->forOrganization($child_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => $costume->id]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$parent_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$child_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_strips_ineligible_org_selection(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $eligible_org = Organization::factory()->create();
        $ineligible_org = Organization::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $eligible_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $ineligible_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($eligible_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$ineligible_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_saves_any_member_club_for_handler_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_org = Organization::factory()->create();
        $other_member_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($event_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $event_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($event_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($other_member_org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $handler_costume->id,
                    'organization_ids' => [$other_member_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertTrue($event_trooper->is_handler);
        $this->assertSame([$other_member_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_saves_any_member_club_when_no_costume_selected(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_org = Organization::factory()->create();
        $other_member_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($event_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $event_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($event_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($other_member_org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => '',
                    'organization_ids' => [$other_member_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertNull($event_trooper->costume_id);
        $this->assertSame([$other_member_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_can_clear_credited_org_selection_when_no_costume_selected(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org->id],
            ]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => '',
                    'organization_selection' => '1',
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertNull($event_trooper->costume_id);
        $this->assertSame([], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_can_clear_credited_org_selection_when_regular_costume_selected(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([
                EventTrooper::COSTUME_ID => $costume->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org->id],
            ]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_selection' => '1',
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame($costume->id, $event_trooper->costume_id);
        $this->assertSame([], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_filters_regular_costume_credit_selection_for_moderator_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $allowed_org = Organization::factory()->create();
        $blocked_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($allowed_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $allowed_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $blocked_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $allowed_org_costume = OrganizationCostume::factory()->forOrganization($allowed_org)->forCostume($costume)->create();
        $blocked_org_costume = OrganizationCostume::factory()->forOrganization($blocked_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($allowed_org_costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($blocked_org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($moderator)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$allowed_org->id, $blocked_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$allowed_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_filters_handler_credit_selection_for_moderator_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $allowed_org = Organization::factory()->create();
        $blocked_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($allowed_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($allowed_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($blocked_org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($moderator)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $handler_costume->id,
                    'organization_ids' => [$allowed_org->id, $blocked_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$allowed_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_filters_no_costume_credit_selection_for_moderator_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $allowed_org = Organization::factory()->create();
        $blocked_org = Organization::factory()->create();
        $event = Event::factory()->withOrganization($allowed_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($allowed_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($blocked_org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($moderator)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => '',
                    'organization_ids' => [$allowed_org->id, $blocked_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$allowed_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_child_unit_moderator_preserves_regular_costume_parent_club_credit(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $parent_org = Organization::factory()->create();
        $child_org = Organization::factory()->withParent($parent_org)->create();
        $event = Event::factory()->withOrganization($child_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($child_org)->asModerator()->create();
        $org_costume = OrganizationCostume::factory()
            ->forOrganization($child_org)
            ->forCostume($costume)
            ->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->withCostumeOrganizationIds([$child_org->id])
            ->asGoing()
            ->create();

        $this->actingAs($moderator)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_selection' => '1',
                    'organization_ids' => [$parent_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$child_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_child_unit_moderator_saves_command_staff_parent_club_credit(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $parent_org = Organization::factory()->create();
        $child_org = Organization::factory()->withParent($parent_org)->create();
        $event = Event::factory()->withOrganization($child_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $command_staff = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($child_org)->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($child_org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($command_staff)
            ->asGoing()
            ->create();

        $this->actingAs($moderator)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $command_staff->id,
                    'organization_selection' => '1',
                    'organization_ids' => [$parent_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$child_org->id], $event_trooper->costume_organization_ids);
    }
}
