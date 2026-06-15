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

class GetEventTrooperOrgOptionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_eligible_orgs_for_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $eligible_org = Organization::factory()->create();
        $ineligible_org = Organization::factory()->create();

        $eligible_org->update([Organization::NODE_PATH => (string) $eligible_org->id]);
        $ineligible_org->update([Organization::NODE_PATH => (string) $ineligible_org->id]);

        $event = Event::factory()->withOrganization($eligible_org)->create();
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

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($eligible_org)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($ineligible_org)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
            .'?costume_id='.$costume->id
        );

        $response->assertOk();
        $response->assertViewHas('org_options', function ($org_options) use ($eligible_org, $ineligible_org) {
            return $org_options->contains('id', $eligible_org->id)
                && ! $org_options->contains('id', $ineligible_org->id);
        });
    }

    public function test_invoke_pre_checks_all_eligible_orgs_when_costume_is_new(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $org->update([Organization::NODE_PATH => (string) $org->id]);

        $event = Event::factory()->withOrganization($org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $old_costume = Costume::factory()->create();
        $new_costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($org)->forCostume($new_costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => $old_costume->id]);

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
            .'?costume_id='.$new_costume->id
        );

        $response->assertOk();
        $response->assertViewHas('credited_ids', fn ($ids) => in_array($org->id, $ids, true));
    }

    public function test_invoke_returns_all_member_clubs_for_handler_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org1->update([Organization::NODE_PATH => (string) $org1->id]);
        $org2->update([Organization::NODE_PATH => (string) $org2->id]);

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $handler_costume = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org1)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
            .'?costume_id='.$handler_costume->id
        );

        $response->assertOk();
        $response->assertViewHas('org_options', function ($org_options) use ($org1, $org2) {
            return $org_options->contains('id', $org1->id)
                && $org_options->contains('id', $org2->id);
        });
        $response->assertViewHas('credited_ids', fn ($ids) => $ids === [$org1->id, $org2->id]);
    }

    public function test_invoke_returns_all_member_clubs_when_changing_from_regular_costume_to_handler(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org1->update([Organization::NODE_PATH => (string) $org1->id]);
        $org2->update([Organization::NODE_PATH => (string) $org2->id]);

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $old_costume = Costume::factory()->create();
        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($old_costume)
            ->withCostumeOrganizationIds([$org1->id])
            ->create();

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
            .'?costume_id='.$handler_costume->id
        );

        $response->assertOk();
        $response->assertViewHas('org_options', function ($org_options) use ($org1, $org2) {
            return $org_options->contains('id', $org1->id)
                && $org_options->contains('id', $org2->id);
        });
        $response->assertViewHas('credited_ids', fn ($ids) => $ids === [$org1->id, $org2->id]);
    }

    public function test_invoke_uses_stored_selection_when_costume_unchanged(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
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

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org1)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->withCostumeOrganizationIds([$org1->id])
            ->create();

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
            .'?costume_id='.$costume->id
        );

        $response->assertOk();
        $response->assertViewHas('credited_ids', fn ($ids) => $ids === [$org1->id]);
    }

    public function test_invoke_returns_all_member_clubs_when_no_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $org->update([Organization::NODE_PATH => (string) $org->id]);

        $event = Event::factory()->withOrganization($org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
        );

        $response->assertOk();
        $response->assertViewHas('org_options', fn ($org_options) => $org_options->contains('id', $org->id));
    }

    public function test_invoke_returns_all_member_clubs_when_changing_from_regular_costume_to_no_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org1->update([Organization::NODE_PATH => (string) $org1->id]);
        $org2->update([Organization::NODE_PATH => (string) $org2->id]);

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $old_costume = Costume::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($old_costume)
            ->withCostumeOrganizationIds([$org1->id])
            ->create();

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
            .'?costume_id='
        );

        $response->assertOk();
        $response->assertViewHas('org_options', function ($org_options) use ($org1, $org2) {
            return $org_options->contains('id', $org1->id)
                && $org_options->contains('id', $org2->id);
        });
        $response->assertViewHas('credited_ids', fn ($ids) => $ids === [$org1->id, $org2->id]);
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

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([EventTrooper::COSTUME_ID => null]);

        $response = $this->actingAs($moderator)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
        );

        $response->assertOk();
        $response->assertViewHas('org_options', function ($org_options) use ($allowed_org, $blocked_org) {
            return $org_options->contains('id', $allowed_org->id)
                && ! $org_options->contains('id', $blocked_org->id);
        });
    }

    public function test_invoke_returns_404_if_event_trooper_belongs_to_different_event(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $other_shift = EventShift::factory()->forEvent($other_event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($other_shift)
            ->forTrooper($trooper)
            ->create();

        $response = $this->actingAs($admin)->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
        );

        $response->assertNotFound();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        $response = $this->get(
            route('admin.events.troopers.org-options', compact('event', 'event_trooper'))
        );

        $response->assertRedirect(route('auth.login'));
    }
}
