<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventMissionAck;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_signs_up_trooper_and_returns_shift_partial(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
    }

    public function test_invoke_says_what_to_do_next_after_signing_up(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
        $flash = json_decode((string) $response->headers->get('X-Flash-Message'), true);
        $this->assertSame("You're on the shift. Pick a costume below to confirm your spot.", $flash['message']);
        $this->assertSame('success', $flash['type']);
        $response->assertSee('data-flash-target="toast"', false);
    }

    public function test_invoke_names_the_friend_who_was_signed_up(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($friend)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['trooper_id' => $friend->id]
        );

        $response->assertOk();
        $this->assertStringStartsWith(
            $friend->display_name.' is ',
            json_decode((string) $response->headers->get('X-Flash-Message'), true)['message']
        );
    }

    public function test_invoke_says_a_second_shift_on_the_same_day_shares_the_troop(): void
    {
        config(['tracker.count_as_one_troop_per_day' => true]);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $day = now()->addDays(10)->setTime(9, 0);
        [$morning, $afternoon] = collect([$day, $day->copy()->setTime(13, 0)])
            ->map(fn ($start) => EventShift::factory()->forEvent($event)->create([
                EventShift::SHIFT_STARTS_AT => $start,
                EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(3),
            ]))
            ->all();
        //  still choosing a costume counts as being on the shift
        EventTrooper::factory()->forEventShift($morning)->forTrooper($trooper)->create([
            EventTrooper::STATUS => \App\Enums\EventTrooperStatus::PENDING,
        ]);

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $afternoon->id]));

        $response->assertOk();
        $this->assertSame(
            "You're signed up. This troop counts as one troop for the day. You're currently signed up for 2 shifts that day.",
            json_decode((string) $response->headers->get('X-Flash-Message'), true)['message']
        );
        $response->assertSee('Together these shifts count as one troop.');
    }

    public function test_invoke_signs_up_trooper_previously_removed_from_shift(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $removed = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->create();
        $removed->delete();

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $removed->id,
            EventTrooper::DELETED_AT => null,
        ]);
    }

    public function test_invoke_saves_organization_id_from_request(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()
            ->withOrganization($organization)
            ->state([Event::TROOPERS_ALLOWED => null])
            ->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $this->actingAs($trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['organization_id' => $organization->id]
        );

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_requires_station_when_shift_has_stations(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        EventShiftStation::factory()->forEventShift($event_shift)->create();

        $this->actingAs($trooper)
            ->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]))
            ->assertOk();

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
        ]);
    }

    public function test_invoke_saves_station_id_from_request(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();

        $this->actingAs($trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['event_shift_station_id' => $station->id]
        );

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $station->id,
        ]);
    }

    public function test_invoke_signs_up_friend_for_selected_station(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($friend)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        $response = $this->actingAs($trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            [
                'trooper_id' => $friend->id,
                'event_shift_station_id' => $station->id,
            ]
        );

        $response->assertOk();
        $response->assertSee("friend-station-picker-{$event_shift->id}", false);
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $friend->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::EVENT_SHIFT_STATION_ID => $station->id,
            EventTrooper::ADDED_BY_TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_invoke_signs_up_friend_when_only_auth_trooper_has_acked_mission_brief(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($friend)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()
            ->withOrganization($organization)
            ->state([Event::REQUIRE_MISSION_BRIEF_ACK => true])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        EventMissionAck::factory()->create([
            EventMissionAck::EVENT_ID => $event->id,
            EventMissionAck::TROOPER_ID => $trooper->id,
        ]);

        $response = $this->actingAs($trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['trooper_id' => $friend->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $friend->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::ADDED_BY_TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_does_not_sign_up_friend_when_auth_trooper_not_going(): void
    {
        $auth_trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($auth_trooper)->forOrganization($organization)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($friend)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $this->actingAs($auth_trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['trooper_id' => $friend->id]
        )->assertOk();

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $friend->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
        ]);
    }
}
