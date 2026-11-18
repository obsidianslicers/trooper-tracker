<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Only event coordinators and up can be picked as an event's coordinator. The one picked can
 * run that event even when someone else created it or it's outside their scope.
 */
class PickedCoordinatorAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['update', 'shifts', 'charity', 'troopers', 'uploads', 'mission-review'];

    private Organization $region;

    private Organization $far_region;

    private Trooper $admin;

    private Trooper $picked;

    private Trooper $member;

    private Event $event;

    private EventShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $club = Organization::factory()->asOrganization()->withNodePath('1:')->create(['name' => 'Club']);
        $this->region = Organization::factory()->asRegion()->withParent($club)->withNodePath('1:2:')->create(['name' => 'Home Region']);
        $other_region = Organization::factory()->asRegion()->withParent($club)->withNodePath('1:4:')->create(['name' => 'Other Region']);
        $this->far_region = Organization::factory()->asRegion()->withParent($club)->withNodePath('1:6:')->create(['name' => 'Far Region']);

        $this->admin = Trooper::factory()->asSystemOwner()->asActive()->withVerifiedEmail()->create();
        $creator = Trooper::factory()->asEventCoordinator()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($creator)->forOrganization($this->region)->asMember()->create();

        //  an event coordinator from another region, so they can't host for the event's region
        $this->picked = Trooper::factory()->asEventCoordinator()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($this->picked)->forOrganization($other_region)->asMember()->create();

        $this->member = Trooper::factory()->asMember()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($this->member)->forOrganization($this->region)->asMember()->create();

        $this->event = Event::factory()->withOrganization($this->region)->withCreatedByTrooper($creator)->create(['name' => 'Coordinated Troop']);
        $this->shift = EventShift::factory()->forEvent($this->event)->create();
        foreach ([$this->picked, $this->member] as $trooper)
        {
            EventTrooper::factory()->forEventShift($this->shift)->forTrooper($trooper)->create([
                EventTrooper::STATUS => EventTrooperStatus::GOING,
            ]);
        }
    }

    public function test_only_event_coordinators_and_up_are_offered(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.update', ['event' => $this->event]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'overview.coordinator_options',
                fn ($options): bool => collect($options)->pluck('value')->contains($this->picked->id)
                    && !collect($options)->pluck('value')->contains($this->member->id)
            ));
    }

    public function test_a_member_cannot_be_picked(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.events.update', ['event' => $this->event]), $this->payload($this->region, $this->member))
            ->assertSessionHasErrors(Event::COORDINATOR_TROOPER_ID);

        $this->assertNull($this->event->fresh()->coordinator_trooper_id);
    }

    public function test_an_event_coordinator_picked_for_someone_elses_event_runs_it(): void
    {
        foreach (self::PAGES as $page)
        {
            $this->actingAs($this->picked)
                ->get(route('admin.events.'.$page, ['event' => $this->event]))
                ->assertForbidden();
        }

        $this->pick($this->picked);

        //  the old tab pages now open the matching tab of the one event page
        foreach (self::PAGES as $page)
        {
            $this->actingAs($this->picked)
                ->get(route('admin.events.'.$page, ['event' => $this->event]))
                ->assertStatus($page === 'update' ? 200 : 302);
        }

        //  and it's under My Events
        $this->actingAs($this->picked)
            ->get(route('admin.events.list'))
            ->assertOk()
            ->assertSee('Coordinated Troop');
    }

    public function test_the_picked_coordinator_saves_the_event_but_cannot_move_it_where_they_cannot_host(): void
    {
        $this->pick($this->picked);

        $this->actingAs($this->picked)
            ->get(route('admin.events.update', ['event' => $this->event]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('overview.host_name', 'Home Region')
                ->where('overview.can_change_host', false));

        $this->actingAs($this->picked)
            ->post(route('admin.events.update', ['event' => $this->event]), $this->payload($this->region, $this->picked))
            ->assertOk()
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Troop', $this->event->fresh()->name);

        $this->actingAs($this->picked)
            ->post(route('admin.events.update', ['event' => $this->event]), $this->payload($this->far_region, $this->picked))
            ->assertSessionHasErrors(Event::ORGANIZATION_ID);

        $this->assertSame($this->region->id, $this->event->fresh()->organization_id);
    }

    public function test_a_moderator_picked_outside_their_scope_runs_it(): void
    {
        $moderator = Trooper::factory()->asModerator()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($this->far_region)->asModerator()->create();
        EventTrooper::factory()->forEventShift($this->shift)->forTrooper($moderator)->create([
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $this->actingAs($moderator)->get(route('admin.events.update', ['event' => $this->event]))->assertForbidden();

        $this->pick($moderator);

        $this->actingAs($moderator)->get(route('admin.events.update', ['event' => $this->event]))->assertOk();
    }

    public function test_access_ends_when_the_coordinator_stops_going(): void
    {
        $this->pick($this->picked);

        EventTrooper::where(EventTrooper::TROOPER_ID, $this->picked->id)->sole()
            ->update([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);

        $this->assertNull($this->event->fresh()->coordinator_trooper_id);
        $this->actingAs($this->picked)
            ->get(route('admin.events.update', ['event' => $this->event]))
            ->assertForbidden();
    }

    public function test_access_ends_when_the_coordinator_is_no_longer_an_event_coordinator(): void
    {
        $this->pick($this->picked);
        $this->picked->update([Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER]);

        $this->actingAs($this->picked->fresh())
            ->get(route('admin.events.update', ['event' => $this->event]))
            ->assertForbidden();
    }

    private function pick(Trooper $trooper): void
    {
        $this->event->update([Event::COORDINATOR_TROOPER_ID => $trooper->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Organization $organization, Trooper $coordinator): array
    {
        return [
            'organization_id' => $organization->id,
            'name' => 'Renamed Troop',
            Event::EVENT_TYPE_ID => EventType::idNamed('Regular'),
            'status' => EventStatus::OPEN->value,
            'event_start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'event_end' => now()->addDays(7)->addHours(4)->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            Event::COORDINATOR_TROOPER_ID => $coordinator->id,
        ];
    }
}
