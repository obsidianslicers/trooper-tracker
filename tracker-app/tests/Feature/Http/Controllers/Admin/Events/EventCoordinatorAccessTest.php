<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * An event coordinator creates events for organizations they belong to and manages only
 * the events they created, without any other command staff access.
 */
class EventCoordinatorAccessTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $event_coordinator;

    private Organization $club;

    private Organization $region;

    private Organization $unit;

    private Organization $other_region;

    protected function setUp(): void
    {
        parent::setUp();

        $this->club = Organization::factory()->asOrganization()->withNodePath('1:')->create(['name' => 'Club']);
        $this->region = Organization::factory()->asRegion()->withParent($this->club)->withNodePath('1:2:')->create(['name' => 'Home Region']);
        $this->unit = Organization::factory()->asUnit()->withParent($this->region)->withNodePath('1:2:3:')->create(['name' => 'Some Squad']);
        $this->other_region = Organization::factory()->asRegion()->withParent($this->club)->withNodePath('1:4:')->create(['name' => 'Other Region']);

        $this->event_coordinator = Trooper::factory()->asEventCoordinator()->withVerifiedEmail()->create();

        TrooperAssignment::factory()
            ->forTrooper($this->event_coordinator)
            ->forOrganization($this->region)
            ->asMember()
            ->create();
    }

    public function test_event_coordinator_has_no_command_staff_dashboard(): void
    {
        $this->actingAs($this->event_coordinator)
            ->get(route('admin.display'))
            ->assertForbidden();
    }

    public function test_event_coordinator_sees_event_links_instead_of_command_staff(): void
    {
        $this->actingAs($this->event_coordinator)
            ->get(route('events.list'))
            ->assertOk()
            ->assertSee(route('admin.events.create'), false)
            ->assertSee('My Events')
            ->assertDontSee('href="'.route('admin.display').'"', false);
    }

    public function test_event_coordinator_can_host_for_organizations_they_belong_to(): void
    {
        $response = $this->actingAs($this->event_coordinator)->get(route('pickers.organization', [
            'property' => 'organization_id',
            'event_host_only' => true,
        ]));

        $response->assertOk()
            ->assertSee('Home Region')
            ->assertSee('Club')
            ->assertDontSee('Some Squad')
            ->assertDontSee('Other Region');
    }

    public function test_only_current_memberships_count_for_hosting(): void
    {
        //  notify-only on the other region doesn't make them a member of it
        TrooperAssignment::factory()
            ->forTrooper($this->event_coordinator)
            ->forOrganization($this->other_region)
            ->withShouldNotify()
            ->create();

        $hostable = Organization::eventHostableBy($this->event_coordinator)->pluck(Organization::NAME)->all();

        $this->assertEqualsCanonicalizing(['Club', 'Home Region'], $hostable);

        //  nor does a membership that has ended
        $former_member = Trooper::factory()->asEventCoordinator()->create();
        TrooperAssignment::factory()
            ->forTrooper($former_member)
            ->forOrganization($this->unit)
            ->asMember()
            ->create()
            ->delete();

        $this->assertSame(0, Organization::eventHostableBy($former_member)->count());
    }

    public function test_event_coordinator_creates_an_event_they_then_own(): void
    {
        Queue::fake([SendEventCreatedNotificationsJob::class]);

        $this->actingAs($this->event_coordinator)
            ->post(route('admin.events.create'), $this->eventPayload($this->region))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tt_events', [
            Event::NAME => 'Coordinator Troop',
            Event::ORGANIZATION_ID => $this->region->id,
            Event::CREATED_ID => $this->event_coordinator->id,
        ]);
    }

    public function test_event_coordinator_cannot_create_an_event_for_another_organization(): void
    {
        $this->actingAs($this->event_coordinator)
            ->post(route('admin.events.create'), $this->eventPayload($this->other_region))
            ->assertSessionHasErrors(Event::ORGANIZATION_ID);

        $this->assertDatabaseMissing('tt_events', [Event::NAME => 'Coordinator Troop']);
    }

    public function test_event_coordinator_manages_only_their_own_events(): void
    {
        $own = Event::factory()->withOrganization($this->region)->withCreatedByTrooper($this->event_coordinator)->create(['name' => 'Own Troop']);
        $others = Event::factory()->withOrganization($this->region)->create(['name' => 'Someone Elses Troop']);

        $this->actingAs($this->event_coordinator)
            ->get(route('admin.events.list'))
            ->assertOk()
            ->assertSee('Own Troop')
            ->assertDontSee('Someone Elses Troop');

        //  no breadcrumb into the Command Staff dashboard they can't open
        $this->actingAs($this->event_coordinator)->get(route('admin.events.update', ['event' => $own]))
            ->assertOk()
            ->assertDontSee('href="'.route('admin.display').'"', false);
        $this->actingAs($this->event_coordinator)->get(route('admin.events.troopers', ['event' => $own]))->assertOk();
        $this->actingAs($this->event_coordinator)->get(route('admin.events.update', ['event' => $others]))->assertForbidden();
        $this->actingAs($this->event_coordinator)->get(route('admin.events.troopers', ['event' => $others]))->assertForbidden();
    }

    public function test_event_coordinator_cannot_move_their_event_to_another_organization(): void
    {
        $own = Event::factory()->withOrganization($this->region)->withCreatedByTrooper($this->event_coordinator)->create();

        $this->actingAs($this->event_coordinator)
            ->post(route('admin.events.update', ['event' => $own]), $this->eventPayload($this->other_region))
            ->assertSessionHasErrors(Event::ORGANIZATION_ID);

        $this->assertSame($this->region->id, $own->fresh()->organization_id);
    }

    public function test_a_copied_event_belongs_to_the_coordinator(): void
    {
        $source = Event::factory()->withOrganization($this->region)->create();

        $this->actingAs($this->event_coordinator)
            ->post(route('admin.events.copy', ['event' => $source]), [
                'name' => 'Copied By Coordinator',
                'event_start' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect();

        $copy = Event::where(Event::NAME, 'Copied By Coordinator')->sole();

        $this->assertSame($this->event_coordinator->id, $copy->created_id);
        $this->assertSame($this->region->id, $copy->organization_id);

        $this->actingAs($this->event_coordinator)
            ->get(route('admin.events.update', ['event' => $copy]))
            ->assertOk();
    }

    public function test_event_page_offers_edit_and_copy_by_permission(): void
    {
        $own = Event::factory()->withOrganization($this->region)->withCreatedByTrooper($this->event_coordinator)->create();
        $same_region = Event::factory()->withOrganization($this->region)->create();
        $other_region = Event::factory()->withOrganization($this->other_region)->create();

        $this->actingAs($this->event_coordinator)->get(route('events.display', ['event' => $own]))
            ->assertOk()
            ->assertSee(route('admin.events.update', ['event' => $own]), false)
            ->assertSee(route('admin.events.copy', ['event' => $own]), false);

        $this->actingAs($this->event_coordinator)->get(route('events.display', ['event' => $same_region]))
            ->assertOk()
            ->assertDontSee(route('admin.events.update', ['event' => $same_region]), false)
            ->assertSee(route('admin.events.copy', ['event' => $same_region]), false);

        $this->actingAs($this->event_coordinator)->get(route('events.display', ['event' => $other_region]))
            ->assertOk()
            ->assertDontSee(route('admin.events.update', ['event' => $other_region]), false)
            ->assertDontSee(route('admin.events.copy', ['event' => $other_region]), false);
    }

    public function test_event_coordinator_copies_events_of_organizations_they_belong_to(): void
    {
        $same_region = Event::factory()->withOrganization($this->region)->create();
        $other_region = Event::factory()->withOrganization($this->other_region)->create();

        $this->actingAs($this->event_coordinator)->get(route('admin.events.copy', ['event' => $same_region]))->assertOk();
        $this->actingAs($this->event_coordinator)->get(route('admin.events.copy', ['event' => $other_region]))->assertForbidden();
    }

    public function test_event_coordinator_cannot_upload_photos_to_someone_elses_event(): void
    {
        Storage::fake('public');

        $others = Event::factory()->withOrganization($this->region)->create();

        $this->actingAs($this->event_coordinator)
            ->post(route('admin.events.upload-image', ['event' => $others]), [
                'images' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
            ])
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(Organization $organization): array
    {
        return [
            'organization_id' => $organization->id,
            'name' => 'Coordinator Troop',
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
            'create_forum_thread' => false,
        ];
    }
}
