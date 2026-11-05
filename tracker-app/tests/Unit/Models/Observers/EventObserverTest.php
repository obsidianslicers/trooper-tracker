<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Events\EventDetailsChanged;
use App\Models\Event;
use App\Models\Observers\EventObserver;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_sets_primary_organization_to_top_level_club(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();

        $subject = new EventObserver;
        $event = Event::factory()->withOrganization($region)->make();

        $subject->creating($event);

        $this->assertSame($club->id, $event->primary_organization_id);
    }

    public function test_updating_re_derives_primary_organization_when_hosting_organization_changes(): void
    {
        $original_club = Organization::factory()->asOrganization()->create();
        $new_club = Organization::factory()->asOrganization()->create();
        $new_region = Organization::factory()->asRegion()->withParent($new_club)->create();

        $event = Event::factory()->withOrganization($original_club)->create();

        $event->organization_id = $new_region->id;

        $subject = new EventObserver;
        $subject->updating($event);

        $this->assertSame($new_club->id, $event->primary_organization_id);
    }

    public function test_updating_leaves_primary_organization_untouched_when_hosting_organization_unchanged(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $event = Event::factory()->withOrganization($club)->create();

        $event->name = 'Renamed Event';

        $subject = new EventObserver;
        $subject->updating($event);

        $this->assertSame($club->id, $event->primary_organization_id);
    }

    public function test_updated_fires_event_details_changed_with_the_changed_fields(): void
    {
        Queue::fake();
        $event = Event::factory()->create();
        EventFacade::fake([EventDetailsChanged::class]);

        $event->name = 'Renamed Event';
        $event->save();

        EventFacade::assertDispatched(EventDetailsChanged::class, fn (EventDetailsChanged $changed): bool => $changed->event->is($event)
            && $changed->changed_fields === [Event::NAME]);
    }

    public function test_updated_does_not_fire_event_details_changed_for_other_fields(): void
    {
        Queue::fake();
        $event = Event::factory()->create();
        EventFacade::fake([EventDetailsChanged::class]);

        $event->thread_id = 99;
        $event->save();

        EventFacade::assertNotDispatched(EventDetailsChanged::class);
    }
}
