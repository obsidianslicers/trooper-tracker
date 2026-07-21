<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

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

class SignUpUpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_trooper_status_for_owner(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]), [
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->create();

        $response = $this->post(route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]), [
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_rejects_clearing_station_for_stationed_shift(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper]),
            ['event_shift_station_id' => ''],
        );

        $response->assertSessionHasErrors(EventTrooper::EVENT_SHIFT_STATION_ID);
        $this->assertSame($station->id, $event_trooper->fresh()->event_shift_station_id);
    }

    public function test_invoke_updates_organization_id_and_clears_costumes(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org_a = Organization::factory()->create();
        $org_b = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org_a)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org_b)->asMember()->create();

        $event = Event::factory()
            ->withOrganization($org_a)
            ->state([Event::TROOPERS_ALLOWED => null])
            ->create();
        foreach ([$org_a, $org_b] as $org)
        {
            EventOrganization::factory()
                ->state([
                    EventOrganization::EVENT_ID => $event->id,
                    EventOrganization::ORGANIZATION_ID => $org->id,
                    EventOrganization::CAN_ATTEND => true,
                    EventOrganization::TROOPERS_ALLOWED => null,
                ])
                ->create();
        }
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $costume = Costume::factory()->create();
        $oc = OrganizationCostume::factory()
            ->state([OrganizationCostume::ORGANIZATION_ID => $org_a->id, OrganizationCostume::COSTUME_ID => $costume->id])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $oc->id])
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ORGANIZATION_ID => $org_a->id,
                EventTrooper::COSTUME_ID => $costume->id,
            ])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['organization_id' => $org_b->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::ORGANIZATION_ID => $org_b->id,
            EventTrooper::COSTUME_ID => null,
            EventTrooper::BACKUP_COSTUME_ID => null,
        ]);
    }

    public function test_invoke_organization_update_returns_403_when_target_org_at_capacity(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org_a = Organization::factory()->create();
        $org_b = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org_a)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org_b)->asMember()->create();

        $event = Event::factory()
            ->withOrganization($org_a)
            ->state([Event::TROOPERS_ALLOWED => null])
            ->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $org_a->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => null,
            ])
            ->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $org_b->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        // Fill org_b slot
        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false, EventTrooper::ORGANIZATION_ID => $org_b->id])
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false, EventTrooper::ORGANIZATION_ID => $org_a->id])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['organization_id' => $org_b->id]
        );

        $response->assertOk();
        // organization_id should be unchanged
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::ORGANIZATION_ID => $org_a->id,
        ]);
    }

    public function test_invoke_organization_update_forbidden_when_no_ownership(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $other_trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($other_trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($other_trooper)
            ->asGoing()
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['organization_id' => $organization->id]
        );

        $response->assertForbidden();
    }

    public function test_invoke_costume_update_sets_is_handler_true_for_handler_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();
        $handler_costume = Costume::factory()->state(['name' => Costume::HANDLER])->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShiftStation($station)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['costume_id' => $handler_costume->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $station->id,
            EventTrooper::IS_HANDLER => true,
            EventTrooper::COSTUME_ID => $handler_costume->id,
        ]);
    }

    public function test_invoke_costume_update_sets_is_handler_true_for_command_staff_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $command_staff_costume = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['costume_id' => $command_staff_costume->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::IS_HANDLER => true,
            EventTrooper::COSTUME_ID => $command_staff_costume->id,
        ]);
    }

    public function test_invoke_costume_update_sets_is_handler_false_for_regular_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => null,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $regular_costume = Costume::factory()->state(['name' => 'Stormtrooper'])->create();
        $oc = OrganizationCostume::factory()
            ->state([OrganizationCostume::ORGANIZATION_ID => $organization->id, OrganizationCostume::COSTUME_ID => $regular_costume->id])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $oc->id])
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => true,
                EventTrooper::ORGANIZATION_ID => $organization->id,
            ])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['costume_id' => $regular_costume->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::IS_HANDLER => false,
            EventTrooper::COSTUME_ID => $regular_costume->id,
        ]);
    }

    public function test_invoke_costume_update_demotes_to_stand_by_when_handler_pool_full(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()
            ->withOrganization($organization)
            ->state([Event::HANDLERS_ALLOWED => 1])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        // Fill the handler pool (1/1)
        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => true])
            ->create();

        $handler_costume = Costume::factory()->state(['name' => Costume::HANDLER])->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['costume_id' => $handler_costume->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
            EventTrooper::IS_HANDLER => true,
        ]);
    }

    public function test_invoke_costume_update_promotes_standby_handler_when_handler_switches_to_regular(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => null,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $standby_handler = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->state([EventTrooper::IS_HANDLER => true, EventTrooper::STATUS => EventTrooperStatus::STAND_BY])
            ->create();

        $handler_costume = Costume::factory()->state(['name' => Costume::HANDLER])->create();
        $regular_costume = Costume::factory()->state(['name' => 'Stormtrooper'])->create();
        $oc = OrganizationCostume::factory()
            ->state([OrganizationCostume::ORGANIZATION_ID => $organization->id, OrganizationCostume::COSTUME_ID => $regular_costume->id])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $oc->id])
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => true,
                EventTrooper::COSTUME_ID => $handler_costume->id,
                EventTrooper::ORGANIZATION_ID => $organization->id,
            ])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['costume_id' => $regular_costume->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $standby_handler->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_station_update_moves_going_trooper_to_standby_when_target_station_is_full(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $old_station = EventShiftStation::factory()->forEventShift($event_shift)->withTroopersAllowed(2)->create();
        $full_station = EventShiftStation::factory()->forEventShift($event_shift)->withTroopersAllowed(1)->create();

        EventTrooper::factory()
            ->forEventShiftStation($full_station)
            ->asGoing()
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShiftStation($old_station)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['event_shift_station_id' => $full_station->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $full_station->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }
}
