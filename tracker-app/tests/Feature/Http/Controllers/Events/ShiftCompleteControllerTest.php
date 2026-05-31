<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShiftCompleteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_not_found_for_invalid_status_token(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->create();

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => 'invalid-token',
        ]));

        $response->assertNotFound();
    }

    public function test_invoke_updates_status_with_valid_token(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');
    }

    public function test_invoke_shows_club_select_when_costume_has_multiple_eligible_clubs(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        // The observer recomputes costume_organization_ids on save; set it directly after creation.
        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete-club-select');
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_invoke_shows_club_select_even_when_organization_id_is_forced(): void
    {
        // organization_id is for capacity tracking only; credit selection (club-select form)
        // must still be shown whenever the trooper has multiple eligible parent clubs.
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::ORGANIZATION_ID => $org1->id]);

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete-club-select');
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_invoke_skips_club_select_when_only_one_eligible_club(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // trooper is only a member of org1, not org2
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');
        $this->assertSame(EventTrooperStatus::ATTENDED, $event_trooper->fresh()->status);
    }

    public function test_invoke_skips_club_select_when_all_eligible_clubs_share_same_parent(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        // One top-level parent with two child orgs — only one parent to choose from.
        $parent_org = Organization::factory()->create();
        $child_org1 = Organization::factory()->create([Organization::PARENT_ID => $parent_org->id]);
        $child_org2 = Organization::factory()->create([Organization::PARENT_ID => $parent_org->id]);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($child_org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($child_org2)->asMember()->create();

        $event = Event::factory()->withOrganization($parent_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$child_org1->id, $child_org2->id])]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');
        $this->assertSame(EventTrooperStatus::ATTENDED, $event_trooper->fresh()->status);
    }

    public function test_empty_costume_orgs_with_single_club_snapshots_org_at_confirmation(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event = Event::factory()->withOrganization($org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create(['is_handler' => true]);

        // Ensure costume_organization_ids is empty (Handler costumes have no OrganizationCostume records)
        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => null]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::ATTENDED, $fresh->status);
        $this->assertEqualsCanonicalizing([$org->id], $fresh->costume_organization_ids);
    }

    public function test_empty_costume_orgs_with_multiple_clubs_shows_club_select(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create(['is_handler' => true]);

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => null]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete-club-select');
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_invoke_shows_club_select_for_handler_when_observer_limited_costume_org_ids_to_one_club(): void
    {
        // Regression: when a handler is in two clubs but the event's can_attend only
        // included one, the observer sets costume_organization_ids to just that one club.
        // getEligibleCreditOrganizations() must bypass that and return the full membership
        // so the club-select form is shown.
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $handler_costume = Costume::factory()->state(['name' => Costume::HANDLER])->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        // Simulate the observer having set costume_id + limited costume_organization_ids
        // to only org1 (because only org1 had can_attend = true for this event).
        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update([
                'costume_id' => $handler_costume->id,
                'costume_organization_ids' => json_encode([$org1->id]),
            ]);
        $event_trooper->refresh();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete-club-select');
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }
}
