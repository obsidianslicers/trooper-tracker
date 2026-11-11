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
