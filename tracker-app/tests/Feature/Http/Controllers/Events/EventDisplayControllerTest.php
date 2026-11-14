<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_details_page(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertViewIs('pages.events.event-display');
        $response->assertDontSee('Does not count toward troop totals', false);
        $response->assertDontSee('not-counted-logo', false);
    }

    public function test_invoke_marks_an_event_whose_type_does_not_count(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event_type = EventType::factory()->uncounted()->create([EventType::NAME => 'Armor Party Plus']);
        $event = Event::factory()->create([Event::EVENT_TYPE_ID => $event_type->id]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        //  the info icon takes the unit logo's place in the title bar; the sentence says why
        $response->assertSee('not-counted-logo', false);
        $response->assertDontSee('organization-32x32.png', false);
        $response->assertDontSee('not-counted-pill', false);
        $response->assertSee('Non-troop-credit event: Does not count toward troop totals.');
        //  at the top of the details and again above the roster, where troopers sign up
        $this->assertSame(2, substr_count($response->getContent(), 'not-counted-notice'));
    }

    public function test_invoke_shows_every_member_the_event_coordinator(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $coordinator = Trooper::factory()->withDisplayName('TK-421 Coordinator')->create();
        $event = Event::factory()->withCreatedByTrooper($coordinator)->create();

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Commanding Officer', 'Event Coordinator', 'TK-421 Coordinator']);
        $response->assertSee(route('service-records.trooper', ['trooper' => $coordinator->id]), false);
    }

    public function test_invoke_leaves_out_the_coordinator_when_the_event_has_none(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create([Event::CREATED_ID => null]);

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertDontSee('Event Coordinator');
    }

    public function test_invoke_keeps_a_tentative_sign_up_selected_inside_the_tentative_cutoff(): void
    {
        config(['tracker.tentative_cutoff_days' => '7']);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $starts_at = now()->addDays(3);
        $event = Event::factory()->withOrganization($organization)->create([
            Event::EVENT_START => $starts_at,
            Event::EVENT_END => $starts_at->copy()->addHours(4),
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
        ]);
        $shift = EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => $starts_at,
            EventShift::SHIFT_ENDS_AT => $starts_at->copy()->addHours(4),
        ]);
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        //  the browser would otherwise show the first option, "Going"
        $this->assertMatchesRegularExpression('/<option value="tentative"\s+selected>/', $response->getContent());
    }

    public function test_invoke_says_two_shifts_on_one_day_count_as_one_troop_before_and_after_signing_up(): void
    {
        config(['tracker.one_troop_per_day' => true]);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $day = now()->addDays(10)->setTime(9, 0);
        $event = Event::factory()->withOrganization($organization)->create([
            Event::EVENT_START => $day,
            Event::EVENT_END => $day->copy()->setTime(20, 0),
        ]);
        $morning = $this->shiftAt($event, $day);
        $afternoon = $this->shiftAt($event, $day->copy()->setTime(13, 0));
        $evening = $this->shiftAt($event, $day->copy()->setTime(17, 0));
        EventTrooper::factory()->forEventShift($morning)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        //  for everyone, at the top of the details and again above the roster
        $this->assertSame(2, substr_count($response->getContent(), 'One troop per day: more than one shift on the same day counts as one troop.'));
        //  at the afternoon and evening shifts, before signing up
        $this->assertSame(2, substr_count($response->getContent(), 'same-day-notice--warning'));
        $response->assertSeeInOrder([
            'id="shift-container-'.$afternoon->id.'"',
            'You&#039;re already signed up for '.$morning->compact_time_display.' on '.$day->format('l, M j').'.',
            'This shift won&#039;t add another troop:',
        ], false);

        EventTrooper::factory()->forEventShift($afternoon)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        //  once signed up for both, each of them says they share the troop
        $response->assertSeeInOrder([
            'id="shift-container-'.$morning->id.'"',
            'You&#039;re also signed up for '.$afternoon->compact_time_display,
            'Together these shifts count as one troop.',
            'id="shift-container-'.$afternoon->id.'"',
            'You&#039;re also signed up for '.$morning->compact_time_display,
            'id="shift-container-'.$evening->id.'"',
            'You&#039;re already signed up for '.$morning->compact_time_display.' and '.$afternoon->compact_time_display,
        ], false);
    }

    public function test_invoke_says_nothing_about_one_troop_per_day_without_two_shifts_on_a_day(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $day = now()->addDays(10)->setTime(9, 0);
        $two_shift_event = Event::factory()->create();
        $this->shiftAt($two_shift_event, $day);
        $this->shiftAt($two_shift_event, $day->copy()->setTime(13, 0));

        //  off
        $this->actingAs($trooper)->get(route('events.display', ['event' => $two_shift_event->id]))
            ->assertOk()
            ->assertDontSee('One troop per day');

        //  on, but the shifts are on different days
        config(['tracker.one_troop_per_day' => true]);
        $multi_day_event = Event::factory()->create();
        $this->shiftAt($multi_day_event, $day);
        $this->shiftAt($multi_day_event, $day->copy()->addDay());

        $this->actingAs($trooper)->get(route('events.display', ['event' => $multi_day_event->id]))
            ->assertOk()
            ->assertDontSee('One troop per day')
            ->assertDontSee('same-day-notice', false);
    }

    private function shiftAt(Event $event, \Carbon\Carbon $start): EventShift
    {
        return EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(3),
        ]);
    }

    public function test_invoke_displays_charity_hours_and_notes_without_funds_or_name(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        EventShift::factory()
            ->forEvent($event)
            ->create([
                EventShift::CHARITY_HOURS => 4,
                EventShift::CHARITY_NOTES => 'Community service hours only',
            ]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('Charity');
        $response->assertSee('Hours: 4');
        $response->assertSee('Community service hours only');
    }

    public function test_invoke_displays_station_count_from_visible_going_roster(): void
    {
        $viewer = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $station = EventShiftStation::factory()
            ->forEventShift($shift)
            ->withName('Docking Bay')
            ->withTroopersAllowed(3)
            ->create();

        EventTrooper::factory()
            ->forEventShiftStation($station)
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->asGoing()
            ->create();
        EventTrooper::factory()
            ->forEventShiftStation($station)
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->asGoing()
            ->create();
        EventTrooper::factory()
            ->forEventShiftStation($station)
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->create([EventTrooper::STATUS => EventTrooperStatus::STAND_BY]);

        $response = $this->actingAs($viewer)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Docking Bay', '2/3']);
    }

    public function test_invoke_shows_trooping_as_label_when_trooper_has_single_eligible_org_with_limit(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('Trooping as ' . $organization->name);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('events.display', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
