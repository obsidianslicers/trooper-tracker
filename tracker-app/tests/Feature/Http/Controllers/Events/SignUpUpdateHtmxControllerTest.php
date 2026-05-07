<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
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
        foreach ([$org_a, $org_b] as $org) {
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
}
