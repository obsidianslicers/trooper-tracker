<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class ShowEventControllerTest extends TestCase
{
    use BuildsTroopCorrections;
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    public function test_invoke_displays_event_details_page(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/Display')
            ->where('event.name', $event->name)
            ->missing('event.id')
            ->where('event.not_counted_notice', null)
            ->whereNot('event.header.logo_url', null));
    }

    public function test_invoke_marks_an_event_whose_type_does_not_count(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event_type = EventType::factory()->uncounted()->create([EventType::NAME => 'Armor Party Plus']);
        $event = Event::factory()->create([Event::EVENT_TYPE_ID => $event_type->id]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        //  the info icon takes the unit logo's place in the title bar; the sentence says why,
        //  at the top of the details and again above the roster (NotCountedNotice.svelte)
        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.header.logo_url', null)
            ->where('event.not_counted_notice', 'Non-troop-credit event: Does not count toward troop totals.'));
    }

    public function test_invoke_shows_every_member_the_event_coordinator(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $coordinator = Trooper::factory()->withDisplayName('TK-421 Coordinator')->create();
        $event = Event::factory()->withCreatedByTrooper($coordinator)->create();

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.coordinator.name', 'TK-421 Coordinator')
            ->where('event.coordinator.url', route('service-records.trooper', ['trooper' => $coordinator->id])));
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
        $response->assertInertia(fn (Assert $page) => $page->where('event.coordinator.name', 'TK-422 Picked'));
    }

    public function test_invoke_leaves_out_the_coordinator_when_the_event_has_none(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create([Event::CREATED_ID => null]);

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('event.coordinator', null));
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
        //  the row's plans picker keeps Tentative, rather than showing the first option, "Going"
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.going.0.editor.plans.value', EventTrooperStatus::TENTATIVE->value));
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
        //  for everyone, at the top of the details; the shifts say it again where it matters:
        //  at the afternoon and evening shifts, before signing up
        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.one_troop_per_day_notice', 'One troop per day: more than one shift on the same day counts as one troop.')
            ->where('shifts.0.same_day', null)
            ->where('shifts.1.same_day', [
                'is_warning' => true,
                'times' => $morning->compact_time_display,
                'day' => $day->format('l, M j'),
            ])
            ->where('shifts.2.same_day.is_warning', true));

        EventTrooper::factory()->forEventShift($afternoon)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        //  once signed up for both, each of them says they share the troop
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.id', $morning->id)
            ->where('shifts.0.same_day.is_warning', false)
            ->where('shifts.0.same_day.times', $afternoon->compact_time_display)
            ->where('shifts.1.same_day.is_warning', false)
            ->where('shifts.1.same_day.times', $morning->compact_time_display)
            ->where('shifts.2.id', $evening->id)
            ->where('shifts.2.same_day.is_warning', true)
            ->where('shifts.2.same_day.times', $morning->compact_time_display.' and '.$afternoon->compact_time_display));
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
            ->assertInertia(fn (Assert $page) => $page->where('event.one_troop_per_day_notice', null));

        //  on, but the shifts are on different days
        config(['tracker.count_as_one_troop_per_day' => true]);
        $multi_day_event = Event::factory()->create();
        $this->shiftAt($multi_day_event, $day);
        $this->shiftAt($multi_day_event, $day->copy()->addDay());

        $this->actingAs($trooper)->get(route('events.display', ['event' => $multi_day_event->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.one_troop_per_day_notice', null)
                ->where('shifts.0.same_day', null)
                ->where('shifts.1.same_day', null));
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
        $response->assertInertia(fn (Assert $page) => $page
            ->has('event.charity', 1)
            ->where('event.charity.0.name', null)
            ->where('event.charity.0.hours', 4)
            ->where('event.charity.0.notes', 'Community service hours only'));
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
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.stations.0.name', 'Docking Bay')
            ->where('shifts.0.stations.0.going', 2)
            ->where('shifts.0.stations.0.limit', 3));
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
        //  one club with its own limit: the row's Edit says "Trooping as" it (RowEditor.svelte)
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.going.0.editor.organizations.options', [
                ['value' => $organization->id, 'label' => $organization->name],
            ]));
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
        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.quotas.is_unlimited', true)
            ->where('event.quotas.closed_names', fn ($names) => collect($names)->contains('Sith Club')
                && !collect($names)->contains('Clone Club')));
    }

    public function test_invoke_lists_the_clubs_when_one_is_limited(): void
    {
        $member = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $this->clubOnEvent($event, 'Clone Club', true);
        $this->clubOnEvent($event, 'Rebel Club', true, 4);

        $response = $this->actingAs($member)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.quotas.is_unlimited', false)
            ->where('event.quotas.clubs', fn ($clubs) => collect($clubs)->firstWhere('name', 'Rebel Club')['troopers_allowed'] === 4
                && collect($clubs)->firstWhere('name', 'Clone Club')['troopers_allowed'] === null));
    }

    public function test_invoke_offers_a_costume_on_your_row_when_the_event_type_needs_one(): void
    {
        [$trooper, $event] = $this->goingOnEventOfType(EventType::factory()->create());

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->whereNot('shifts.0.going.0.editor.costumes', null)
            ->where('shifts.0.going.0.editor.requires_costume', true)
            ->where('tour.requires_costume', true));
    }

    public function test_invoke_leaves_out_costumes_when_the_event_type_needs_none(): void
    {
        [$trooper, $event] = $this->goingOnEventOfType(EventType::factory()->withoutCostumes()->create());

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        //  the tour's "pick your costume" steps drop out with their example row
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.going.0.editor.costumes', null)
            //  so the Edit area says no costume is needed rather than leaving a gap
            ->where('shifts.0.going.0.editor.requires_costume', false)
            ->where('shifts.0.going.0.costume', null)
            ->where('tour.requires_costume', false));
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
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.sign_up.url', route('events.signup', ['event_shift' => $event_shift->id]))
            ->where('shifts.0.sign_up.requires_costume', false)
            ->where('shifts.0.sign_up.default_costume', 'none'));
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
        $response->assertInertia(fn (Assert $page) => $page
            ->where('my_sign_ups.sign_ups', [])
            ->where('my_sign_ups.has_open_shift', true)
            ->where('shifts.0.id', $event_shift->id)
            ->where('shifts.0.sign_up.requires_costume', true)
            ->where('shifts.0.sign_up.default_costume', (string) $second->id)
            ->where('shifts.0.sign_up.costumes', fn ($costumes) => collect($costumes)->pluck('label')->contains($first->name)));
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
        //  your row has the Edit area, with Cancel; someone else's row has none.
        //  Signed up, so no Sign Up form on that shift; the summary's Change opens your row
        $response->assertInertia(fn (Assert $page) => $page
            ->where('my_sign_ups.sign_ups.0.id', $mine->id)
            ->where('my_sign_ups.sign_ups.0.can_change', true)
            ->where('shifts.0.going', function ($rows) use ($mine, $theirs): bool
            {
                $my_row = collect($rows)->firstWhere('id', $mine->id);
                $their_row = collect($rows)->firstWhere('id', $theirs->id);

                return $my_row['is_mine'] && $my_row['editor']['can_cancel']
                    && !$their_row['is_mine'] && $their_row['editor'] === null;
            })
            ->where('shifts.0.sign_up', null));
    }

    public function test_invoke_gives_the_friends_and_guests_you_brought_an_edit_link(): void
    {
        [$trooper, $event, $friend_sign_up, $guest] = $this->shiftWithAFriendAndAGuest();

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.going', fn ($rows) => collect($rows)->firstWhere('id', $friend_sign_up->id)['editor']['costumes'] !== null)
            ->where('shifts.0.guests.0.id', $guest->id)
            ->where('shifts.0.guests.0.can_cancel', true)
            ->where('shifts.0.guests.0.update_url', route('events.guest-update', ['event_guest' => $guest->id])));
    }

    public function test_invoke_gives_no_edit_link_on_someone_elses_friends_and_guests(): void
    {
        [, $event, $friend_sign_up, $guest] = $this->shiftWithAFriendAndAGuest();
        $other = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($other)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.going', fn ($rows) => collect($rows)->firstWhere('id', $friend_sign_up->id)['editor'] === null)
            ->where('shifts.0.guests.0.name', 'Wookiee Guest')
            ->where('shifts.0.guests.0.can_cancel', false));
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
        //  full, so Sign up joins stand-by, third in line
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.going.0.trooper.name', $going->display_name)
            ->where('shifts.0.stand_by.0.trooper.name', $first_in_line->display_name)
            ->where('shifts.0.stand_by.0.number', 1)
            ->where('shifts.0.stand_by.1.trooper.name', $second_in_line->display_name)
            ->where('shifts.0.stand_by.1.number', 2)
            ->where('shifts.0.sign_up.is_full', true)
            ->where('shifts.0.sign_up.hint', __('events.Full. You\'d be #:place on stand-by, and move up in order if someone going cancels.', ['place' => 3])));
    }

    public function test_invoke_says_how_many_spots_are_left(): void
    {
        [$trooper, $event, $event_shift] = $this->shiftTheTrooperCanSignUpFor([Event::TROOPERS_ALLOWED => 3]);
        EventTrooper::factory()->forEventShift($event_shift)->forTrooper(Trooper::factory()->asActive()->create())->asGoing()->create([EventTrooper::IS_HANDLER => false]);

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.sign_up.hint', '2 spots left')
            ->where('shifts.0.sign_up.is_full', false)
            ->where('shifts.0.fullness.spots_left', 2)
            ->where('shifts.0.fullness.limit', 3));
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
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
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
        //  the page opens the shifts the trooper is on (EventDisplayViewModel)
        $response->assertInertia(fn (Assert $page) => $page
            ->where('shifts.0.id', $other_shift->id)
            ->where('shifts.0.is_mine', false)
            ->where('shifts.1.id', $my_shift->id)
            ->where('shifts.1.is_mine', true));
    }

    private function clubOnEvent(Event $event, string $name, bool $can_attend, ?int $troopers_allowed = null): void
    {
        $club = Organization::factory()->asOrganization()->create([Organization::NAME => $name]);
        EventOrganization::factory()->forEvent($event)->forOrganization($club)->create([
            EventOrganization::CAN_ATTEND => $can_attend,
            EventOrganization::TROOPERS_ALLOWED => $troopers_allowed,
        ]);
    }

    private function moderatorOf(EventTrooper $troop): Trooper
    {
        $moderator = Trooper::factory()->asModerator()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($moderator)
            ->forOrganization($troop->event_shift->event->organization)->asModerator()->create();

        return $moderator;
    }

    /**
     * A roster row's attendance buttons, wherever on the shift the row is listed.
     *
     * @return array<string, mixed>|null
     */
    private function attendanceOn(EventTrooper $troop, Trooper $viewer): ?array
    {
        $response = $this->actingAs($viewer)->get(route('events.display', ['event' => $troop->event_shift->event_id]));
        $response->assertOk();

        $rows = collect($response->viewData('page')['props']['shifts'][0])
            ->filter(fn ($value) => is_array($value) && array_is_list($value))
            ->flatten(1)
            ->filter(fn ($row) => is_array($row) && isset($row['actions']));

        return $rows->firstWhere('id', $troop->id)['actions']['attendance'];
    }

    public function test_invoke_offers_made_it_and_missed_it_on_your_row_after_the_troop(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper, EventTrooperStatus::TENTATIVE);

        $this->assertSame(
            ['url' => route('events.confirm-attendance', ['event_trooper' => $troop->id]), 'answer' => null],
            $this->attendanceOn($troop, $trooper),
        );
    }

    public function test_invoke_keeps_the_answer_on_your_row_so_it_shows_filled(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $troop = $this->troopToConfirm($trooper, EventTrooperStatus::UNABLE_TO_ATTEND);

        $this->assertSame(EventTrooperStatus::UNABLE_TO_ATTEND->value, $this->attendanceOn($troop, $trooper)['answer']);
    }

    public function test_invoke_gives_attendance_buttons_to_moderators_but_not_other_members(): void
    {
        $troop = $this->troopToConfirm(Trooper::factory()->asActive()->create());

        $this->assertNull($this->attendanceOn($troop, Trooper::factory()->asActive()->withVerifiedEmail()->create()));
        $this->assertNotNull($this->attendanceOn($troop, $this->moderatorOf($troop)));
    }

    public function test_invoke_leaves_attendance_alone_after_the_grace_period_or_while_verifying(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->assertNull($this->attendanceOn($this->troopToConfirm($trooper, ended_at: now()->subDays(40)), $trooper));
        $this->assertNull($this->attendanceOn($this->troopToConfirm($trooper, EventTrooperStatus::AWAITING_VERIFICATION), $trooper));
    }

    public function test_invoke_offers_request_credit_on_a_completed_event_without_credit(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->completedShift()->event;

        $response = $this->actingAs($trooper)->get(route('events.display', ['event' => $event->id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('my_sign_ups.request_credit_url', route('events.troop-correction-form', ['event_id' => $event->id])));
    }

    public function test_invoke_does_not_offer_request_credit_with_credit_or_on_an_open_event(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $shift = $this->completedShift();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();
        $open = Event::factory()->create();

        $this->actingAs($trooper)->get(route('events.display', ['event' => $shift->event_id]))
            ->assertInertia(fn (Assert $page) => $page->where('my_sign_ups.request_credit_url', null));
        $this->actingAs($trooper)->get(route('events.display', ['event' => $open->id]))
            ->assertInertia(fn (Assert $page) => $page->where('my_sign_ups.request_credit_url', null));
    }
}
