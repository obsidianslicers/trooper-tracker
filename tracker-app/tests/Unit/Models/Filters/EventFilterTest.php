<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_by_status(): void
    {
        $active_event = Event::factory()->open()->create();
        Event::factory()->closed()->create();

        $request = new Request(['status' => EventStatus::OPEN->value]);
        $subject = new EventFilter($request);

        $query = $subject->apply(Event::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($active_event->id, $query->first()->id);
    }

    public function test_it_can_filter_by_organization(): void
    {
        $organization_a = Organization::factory()->create();
        $organization_b = Organization::factory()->create();
        $event_for_org_a = Event::factory()->withOrganization($organization_a)->create();
        Event::factory()->withOrganization($organization_b)->create();

        $request = new Request(['organization_id' => $organization_a->id]);
        $subject = new EventFilter($request);

        $query = $subject->apply(Event::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($event_for_org_a->id, $query->first()->id);
    }

    public function test_it_can_filter_by_search_term(): void
    {
        $event_to_find = Event::factory()->create(['name' => 'The Galactic Gala']);
        Event::factory()->create(['name' => 'Rebel Base Briefing']);

        $request = new Request(['search_term' => 'Galactic']);
        $subject = new EventFilter($request);

        $query = $subject->apply(Event::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($event_to_find->id, $query->first()->id);
    }

    public function test_it_ignores_search_term_if_too_short(): void
    {
        Event::factory()->create(['name' => 'The Galactic Gala']);
        Event::factory()->create(['name' => 'Rebel Base Briefing']);

        $request = new Request(['search_term' => 'Ga']);
        $subject = new EventFilter($request);

        $query = $subject->apply(Event::query());

        $this->assertEquals(2, $query->count());
    }

    public function test_it_can_apply_multiple_filters(): void
    {
        $organization = Organization::factory()->create();

        // The event we expect to find
        $matching_event = Event::factory()->open()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Active Event at Correct Org',
        ]);

        // Decoys
        Event::factory()->withOrganization($organization)->closed()->create();
        Event::factory()->open()->create();

        $request = new Request([
            'organization_id' => $organization->id,
            'status' => EventStatus::OPEN->value,
        ]);
        $subject = new EventFilter($request);

        $query = $subject->apply(Event::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($matching_event->id, $query->first()->id);
    }
}
