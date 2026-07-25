<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
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
