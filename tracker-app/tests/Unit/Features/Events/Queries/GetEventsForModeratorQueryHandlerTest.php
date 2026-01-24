<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsForModeratorQuery;
use App\Features\Events\Queries\GetEventsForModeratorQueryHandler;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for GetEventsForModeratorQueryHandler.
 *
 * Verifies:
 * - Administrators see all events
 * - Moderators see only events from organizations they moderate
 * - Filter criteria (status, organization, search) are applied correctly
 * - Eager loads organization relationships
 * - Counts event shifts
 * - Returns paginated results
 * - Orders by event_end descending
 * - Preserves query string in pagination
 */
class GetEventsForModeratorQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_administrator_sees_all_events(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(3)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(3, $result->total());
    }

    public function test_invoke_moderator_sees_only_moderated_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        $moderated_org = Organization::factory()->create();
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $other_org = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $moderated_org->id,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $other_org->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $moderator);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(2, $result->total());
    }

    public function test_invoke_filters_by_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::STATUS => EventStatus::OPEN,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::STATUS => EventStatus::CLOSED,
        ]);

        $request = Request::create('/admin/events/list', 'GET', ['status' => EventStatus::OPEN->value]);
        $filter = new EventFilter($request);

        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(2, $result->total());
    }

    public function test_invoke_filters_by_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $org1->id,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org2->id,
        ]);

        $request = Request::create('/admin/events/list', 'GET', ['organization_id' => $org1->id]);
        $filter = new EventFilter($request);

        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(2, $result->total());
    }

    public function test_invoke_filters_by_search_term(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Star Wars Convention',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Comic Con',
        ]);

        $request = Request::create('/admin/events/list', 'GET', ['search_term' => 'Star Wars']);
        $filter = new EventFilter($request);

        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(1, $result->total());
    }

    public function test_invoke_eager_loads_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->items()[0]->relationLoaded('organization'));
    }

    public function test_invoke_counts_event_shifts(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        \App\Models\EventShift::factory()->count(3)->create([
            \App\Models\EventShift::EVENT_ID => $event->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(3, $result->items()[0]->event_shifts_count);
    }

    public function test_invoke_returns_paginated_results(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(30)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $admin, 25);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(30, $result->total());
        $this->assertSame(25, $result->perPage());
        $this->assertSame(1, $result->currentPage());
    }

    public function test_invoke_orders_by_event_end_descending(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event1 = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::EVENT_END => now()->addDays(1),
        ]);
        $event2 = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::EVENT_END => now()->addDays(3),
        ]);
        $event3 = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::EVENT_END => now()->addDays(2),
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $admin);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $items = $result->items();
        $this->assertSame($event2->id, $items[0]->id);
        $this->assertSame($event3->id, $items[1]->id);
        $this->assertSame($event1->id, $items[2]->id);
    }

    public function test_invoke_returns_empty_results_when_no_events_match(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $moderator);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(0, $result->total());
    }

    public function test_invoke_respects_custom_page_size(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        Event::factory()->count(20)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $query = new GetEventsForModeratorQuery($filter, $admin, 10);
        $subject = new GetEventsForModeratorQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertSame(10, $result->perPage());
    }
}
