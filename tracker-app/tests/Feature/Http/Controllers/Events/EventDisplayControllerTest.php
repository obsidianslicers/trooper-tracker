<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
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
        $response->assertSeeInOrder(['Point of Contact', 'Event Coordinator', 'TK-421 Coordinator']);
        $response->assertSee(route('service-records.trooper', ['trooper' => $coordinator->id]), false);
    }

    public function test_invoke_shows_the_picked_coordinator_instead_of_the_creator(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $creator = Trooper::factory()->withDisplayName('TK-421 Creator')->create();
        $picked = Trooper::factory()->withDisplayName('TK-422 Picked')->create();
        $event = Event::factory()->withCreatedByTrooper($creator)->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($picked)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        $event->update([Event::COORDINATOR_TROOPER_ID => $picked->id]);

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['Event Coordinator', 'TK-422 Picked']);
        $response->assertDontSee('TK-421 Creator');
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
        config(['tracker.count_as_one_troop_per_day' => true]);
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
        //  for everyone, once, at the top of the details; the shifts say it again where it matters
        $this->assertSame(1, substr_count($response->getContent(), 'One troop per day: more than one shift on the same day counts as one troop.'));
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
        config(['tracker.count_as_one_troop_per_day' => true]);
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

    public function test_invoke_says_no_limits_when_no_club_is_limited(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $this->clubOnEvent($event, 'Clone Club', true);
        $this->clubOnEvent($event, 'Rebel Club', true);
        $this->clubOnEvent($event, 'Sith Club', false);

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Trooper Quotas', 'No limits', 'Not open to', 'Sith Club']);
        $response->assertDontSee('Troopers / Handlers');
    }

    public function test_invoke_lists_the_clubs_when_one_is_limited(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $this->clubOnEvent($event, 'Clone Club', true);
        $this->clubOnEvent($event, 'Rebel Club', true, 4);

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Trooper Quotas', 'Troopers / Handlers', 'Clone Club', 'Rebel Club', '4']);
        $response->assertDontSee('No limits');
    }

    public function test_invoke_offers_a_costume_on_your_row_when_the_event_type_needs_one(): void
    {
        [$trooper, $event] = $this->goingOnEventOfType(EventType::factory()->create());

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('name="costume_id"', false);
        $response->assertSee('data-tour-example="backup"', false);
    }

    public function test_invoke_leaves_out_costumes_when_the_event_type_needs_none(): void
    {
        [$trooper, $event] = $this->goingOnEventOfType(EventType::factory()->withoutCostumes()->create());

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertDontSee('name="costume_id"', false);
        $response->assertDontSee('name="backup_costume_id"', false);
        //  the tour's "pick your costume" steps drop out with their example row
        $response->assertDontSee('data-tour-example="backup"', false);
        $response->assertDontSee('id="costume-picker-', false);
    }

    public function test_invoke_offers_sign_up_without_a_costume_when_the_event_type_needs_none(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create([
            Event::EVENT_TYPE_ID => EventType::factory()->withoutCostumes()->create()->id,
        ]);
        EventOrganization::factory()->forEvent($event)->forOrganization($organization)->create([
            EventOrganization::CAN_ATTEND => true,
        ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee(route('events.signup-htmx', ['event_shift' => $event_shift->id]), false);
        $response->assertDontSee('id="costume-picker-'.$event_shift->id.'"', false);
    }

    /**
     * @return array{Trooper, Event}
     */
    private function goingOnEventOfType(EventType $event_type): array
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create([
            Event::EVENT_TYPE_ID => $event_type->id,
        ]);
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();

        return [$trooper, $event];
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('events.display', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_offers_a_one_step_sign_up_with_the_last_costume_picked(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        [$first, $second] = collect(['Clone Trooper', 'Scout Trooper'])
            ->map(function (string $name) use ($trooper, $organization)
            {
                $costume = Costume::factory()->state(['name' => $name])->create();
                $organization_costume = OrganizationCostume::factory()
                    ->state([OrganizationCostume::ORGANIZATION_ID => $organization->id, OrganizationCostume::COSTUME_ID => $costume->id])
                    ->create();
                TrooperCostume::factory()
                    ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id])
                    ->create();

                return $costume;
            })
            ->all();
        $past_shift = EventShift::factory()->forEvent(Event::factory()->withOrganization($organization)->create())->create();
        EventTrooper::factory()->forEventShift($past_shift)->forTrooper($trooper)->asGoing()->create([
            EventTrooper::COSTUME_ID => $second->id,
            EventTrooper::SIGNED_UP_AT => now()->subDays(20),
        ]);
        $event = Event::factory()->withOrganization($organization)->create();
        EventOrganization::factory()->state([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
        ])->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee("You're not signed up for this event yet.");
        $response->assertSee('id="costume-picker-'.$event_shift->id.'"', false);
        $this->assertMatchesRegularExpression('/<option value="'.$second->id.'"\s+selected>/', $response->getContent());
        $response->assertSee($first->name);
        $response->assertSee('Attending without a costume');
    }

    public function test_invoke_shows_your_sign_up_as_your_roster_row_with_an_edit_area(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $other = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $mine = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create([
            EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => true,
        ]);
        $theirs = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($other)->asGoing()->create();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('id="my-signup-summary"', false);
        $response->assertSee('roster-row--mine', false);
        $response->assertSee('Cancel my sign-up');
        //  your row has the Edit area, with the only costume picker; someone else's row has neither
        $response->assertSee('id="roster-row-editor-'.$mine->id.'"', false);
        $response->assertDontSee('id="roster-row-editor-'.$theirs->id.'"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'name="costume_id"'));
        //  signed up, so no Sign Up form on that shift
        $response->assertDontSee('id="costume-picker-'.$event_shift->id.'"', false);
        $response->assertDontSee('<option value="cancelled"', false);
        //  the summary's Change link opens your row
        $response->assertSee('data-edit-row="'.$mine->id.'"', false);
    }

    public function test_invoke_gives_the_friends_and_guests_you_brought_an_edit_link(): void
    {
        [$trooper, $event, $friend_sign_up, $guest] = $this->shiftWithAFriendAndAGuest();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('id="roster-row-editor-'.$friend_sign_up->id.'"', false);
        $response->assertSee('id="roster-guest-editor-'.$guest->id.'"', false);
        //  two costume pickers, yours and your friend's, each in its row's Edit area
        $this->assertSame(2, substr_count($response->getContent(), 'name="costume_id"'));
        $this->assertSame(1, substr_count($response->getContent(), route('events.guest-update-htmx', ['event_guest' => $guest->id])));
    }

    public function test_invoke_gives_no_edit_link_on_someone_elses_friends_and_guests(): void
    {
        [, $event, $friend_sign_up, $guest] = $this->shiftWithAFriendAndAGuest();
        $other = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($other)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertDontSee('id="roster-row-editor-'.$friend_sign_up->id.'"', false);
        $response->assertDontSee('id="roster-guest-editor-'.$guest->id.'"', false);
        $response->assertSee('Wookiee Guest');
    }

    public function test_invoke_lists_stand_by_on_its_own_in_sign_up_order(): void
    {
        [$trooper, $event, $event_shift] = $this->shiftTheTrooperCanSignUpFor([Event::TROOPERS_ALLOWED => 1]);
        $going = Trooper::factory()->asActive()->create();
        $first_in_line = Trooper::factory()->asActive()->create();
        $second_in_line = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($going)->asGoing()->withSignedUpAt(now()->subDays(3))->create([EventTrooper::IS_HANDLER => false]);
        //  created out of order: the page sorts by sign-up time
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($second_in_line)->withSignedUpAt(now()->subDay())->create([EventTrooper::STATUS => EventTrooperStatus::STAND_BY, EventTrooper::IS_HANDLER => false]);
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($first_in_line)->withSignedUpAt(now()->subDays(2))->create([EventTrooper::STATUS => EventTrooperStatus::STAND_BY, EventTrooper::IS_HANDLER => false]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSeeInOrder([
            $going->display_name,
            __('events.Stand-by, in sign-up order'),
            $first_in_line->display_name,
            $second_in_line->display_name,
        ]);
        //  full, so Sign up joins stand-by, third in line
        $response->assertSee(__('events.Join stand-by'));
        $response->assertSee(__('events.Full. You\'d be #:place on stand-by, and move up in order if someone going cancels.', ['place' => 3]));
    }

    public function test_invoke_says_how_many_spots_are_left(): void
    {
        [$trooper, $event, $event_shift] = $this->shiftTheTrooperCanSignUpFor([Event::TROOPERS_ALLOWED => 3]);
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper(Trooper::factory()->asActive()->create())->asGoing()->create([EventTrooper::IS_HANDLER => false]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertSee('2 spots left');
        $response->assertSee('1 of 3 going');
        $response->assertDontSee(__('events.Join stand-by'));
    }

    /**
     * A member of a club the event is open to, and a shift of that event they can sign up for.
     *
     * @return array{Trooper, Event, EventShift}
     */
    private function shiftTheTrooperCanSignUpFor(array $event_attributes = []): array
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create($event_attributes);
        EventOrganization::factory()->state([
            EventOrganization::EVENT_ID        => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND      => true,
        ])->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        return [$trooper, $event, $event_shift];
    }

    /**
     * A trooper going to a shift with a friend and a guest they added.
     *
     * @return array{Trooper, Event, EventTrooper, EventGuest}
     */
    private function shiftWithAFriendAndAGuest(): array
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create([
            EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => true,
        ]);
        $friend_sign_up = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($friend)->asGoing()->create([
            EventTrooper::ADDED_BY_TROOPER_ID => $trooper->id,
            EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => true,
        ]);
        $guest = EventGuest::factory()->forEventShift($event_shift)->forTrooper($trooper)->withName('Wookiee Guest')->asGoing()->create();

        return [$trooper, $event, $friend_sign_up, $guest];
    }

    public function test_invoke_opens_the_shifts_the_trooper_is_on(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $other_shift = EventShift::factory()->forEvent($event)->create();
        $my_shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($my_shift)->forTrooper($trooper)->asGoing()->create();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $this->assertStringContainsString('class="collapse show"', $this->shiftHtml($response->getContent(), $my_shift));
        $this->assertStringNotContainsString('class="collapse show"', $this->shiftHtml($response->getContent(), $other_shift));
    }

    /**
     * One shift's part of the event page, up to the next shift.
     */
    private function shiftHtml(string $html, EventShift $event_shift): string
    {
        $start = strpos($html, 'id="shift-container-'.$event_shift->id.'"');
        $this->assertNotFalse($start);
        $end = strpos($html, 'id="shift-container-', $start + 1);

        return substr($html, $start, $end === false ? null : $end - $start);
    }

    private function clubOnEvent(Event $event, string $name, bool $can_attend, ?int $troopers_allowed = null): void
    {
        $club = Organization::factory()->asOrganization()->create([Organization::NAME => $name]);
        EventOrganization::factory()->forEvent($event)->forOrganization($club)->create([
            EventOrganization::CAN_ATTEND => $can_attend,
            EventOrganization::TROOPERS_ALLOWED => $troopers_allowed,
        ]);
    }
}
