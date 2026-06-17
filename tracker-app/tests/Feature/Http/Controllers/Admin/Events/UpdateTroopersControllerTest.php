<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

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
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_troopers_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.troopers');
    }

    public function test_invoke_shows_all_member_clubs_for_handler_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_org = Organization::factory()->create();
        $other_member_org = Organization::factory()->create();
        $event_org->update([Organization::NODE_PATH => (string) $event_org->id]);
        $other_member_org->update([Organization::NODE_PATH => (string) $other_member_org->id]);

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
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($event_org)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($other_member_org)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($handler_costume)
            ->asGoing()
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $event_org->id));
        $this->assertTrue($event_trooper->org_options->contains('id', $other_member_org->id));
    }

    public function test_invoke_shows_regular_costume_org_options(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $org->update([Organization::NODE_PATH => (string) $org->id]);
        $event = Event::factory()->withOrganization($org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asGoing()
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $org->id));
        $this->assertSame($costume->id, $event_trooper->costume_id);
    }

    public function test_invoke_shows_regular_costume_org_options_from_assignments_without_trooper_organization_rows(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Florida Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Rebel Legion']);
        $org1->update([Organization::NODE_PATH => (string) $org1->id]);
        $org2->update([Organization::NODE_PATH => (string) $org2->id]);
        $event = Event::factory()->withOrganization($org1)->create();
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

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();
        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->withCostumeOrganizationIds([$org1->id, $org2->id])
            ->asGoing()
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();
        $response->assertDontSee('(Unattached)');

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $org1->id));
        $this->assertTrue($event_trooper->org_options->contains('id', $org2->id));
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $event_trooper->credited_checked_ids);
    }

    public function test_invoke_shows_member_clubs_when_no_costume_selected(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org1->update([Organization::NODE_PATH => (string) $org1->id]);
        $org2->update([Organization::NODE_PATH => (string) $org2->id]);
        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $response = $this->actingAs($admin)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $org1->id));
        $this->assertTrue($event_trooper->org_options->contains('id', $org2->id));
    }

    public function test_resolve_credited_checked_ids_maps_child_credit_ids_to_checked_parent_ids(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $parent_org = Organization::factory()->create();
        $child_org = Organization::factory()->withParent($parent_org)->create();
        \Illuminate\Support\Facades\DB::table('tt_organizations')
            ->where('id', $child_org->id)
            ->update([
                Organization::PARENT_ID => $parent_org->id,
                Organization::NODE_PATH => $parent_org->id.':'.$child_org->id.':',
            ]);
        $event = Event::factory()->withOrganization($parent_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $child_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($child_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($parent_org)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asGoing()
            ->create();
        $event_trooper->update([EventTrooper::COSTUME_ORGANIZATION_IDS => [$child_org->id]]);

        $this->assertSame($parent_org->id, $child_org->fresh()->parent_id);
        $this->assertSame($parent_org->id, Organization::find($child_org->id)->getPrimaryClub()->id);
        $this->assertSame(
            $parent_org->id,
            Organization::findMany([$child_org->id])->keyBy('id')->get($child_org->id)->getPrimaryClub()->id
        );

        $event_trooper->organization_id = null;

        $this->assertSame([$parent_org->id], $event_trooper->creditedRootOrgIds());
    }

    public function test_invoke_shows_parent_org_checked_when_regular_costume_credit_is_child_org_without_trooper_organization_rows(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $parent_org = Organization::factory()->create(['name' => 'Parent Club']);
        $child_org = Organization::factory()->withParent($parent_org)->create(['name' => 'Child Base']);
        \Illuminate\Support\Facades\DB::table('tt_organizations')
            ->where('id', $child_org->id)
            ->update([
                Organization::PARENT_ID => $parent_org->id,
                Organization::NODE_PATH => $parent_org->id.':'.$child_org->id.':',
            ]);
        $event = Event::factory()->withOrganization($parent_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $child_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($child_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->withCostumeOrganizationIds([$child_org->id])
            ->asGoing()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $response = $this->actingAs($admin)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $parent_org->id));
        $this->assertEqualsCanonicalizing([$parent_org->id], $event_trooper->credited_checked_ids);
    }

    public function test_invoke_filters_org_options_for_moderator_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $allowed_org = Organization::factory()->create();
        $blocked_org = Organization::factory()->create();
        $allowed_org->update([Organization::NODE_PATH => (string) $allowed_org->id]);
        $blocked_org->update([Organization::NODE_PATH => (string) $blocked_org->id]);
        $event = Event::factory()->withOrganization($allowed_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($allowed_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($blocked_org)->asMember()->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $response = $this->actingAs($moderator)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $allowed_org->id));
        $this->assertFalse($event_trooper->org_options->contains('id', $blocked_org->id));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
