<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShiftCompleteClubControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_all_clubs_confirms_attendance_and_credits_all(): void
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
            ->create([
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => $encrypted_status,
            'organization_ids' => [$org1->id, $org2->id],
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::ATTENDED, $fresh->status);
        $this->assertNull($fresh->organization_id);
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $fresh->costume_organization_ids);
    }

    public function test_partial_selection_credits_only_selected_parent(): void
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
            ->create([
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Submit only org1 — org2 should be excluded from credit.
        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => $encrypted_status,
            'organization_ids' => [$org1->id],
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::ATTENDED, $fresh->status);
        $this->assertNull($fresh->organization_id);
        $this->assertSame([$org1->id], $fresh->costume_organization_ids);
    }

    public function test_invalid_org_id_returns_422(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

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

        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => $encrypted_status,
            'organization_ids' => [$other_org->id],
        ]);

        $response->assertStatus(422);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_empty_selection_re_renders_form_with_error(): void
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

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => $encrypted_status,
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete-club-select');
        $response->assertViewHas('error');
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_club_select_denied_when_update_window_closed(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event = Event::factory()->withOrganization($org1)->asClosed()->withEventEnd(Carbon::now()->subDays(31))->create();
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

        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => $encrypted_status,
            'organization_ids' => [$org1->id, $org2->id],
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_empty_costume_orgs_club_select_credits_selected_clubs(): void
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
            ->create([
                'is_handler' => true,
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        // No costume_organization_ids — eligible orgs come from memberships
        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => null]);
        $event_trooper->refresh();

        $encrypted_status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        // Select only org1 — org2 should not receive credit
        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => $encrypted_status,
            'organization_ids' => [$org1->id],
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::ATTENDED, $fresh->status);
        $this->assertNull($fresh->organization_id);
        $this->assertSame([$org1->id], $fresh->costume_organization_ids);
    }

    public function test_invalid_encrypted_status_returns_404(): void
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

        DB::table('tt_event_troopers')
            ->where('id', $event_trooper->id)
            ->update(['costume_organization_ids' => json_encode([$org1->id, $org2->id])]);
        $event_trooper->refresh();

        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => 'invalid-token',
            'organization_ids' => [$org1->id, $org2->id],
        ]);

        $response->assertNotFound();
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }
}
