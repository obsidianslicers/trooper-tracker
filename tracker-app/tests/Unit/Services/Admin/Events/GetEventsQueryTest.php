<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Admin\Events\GetEventsQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for GetEventsQuery.
 *
 * Verifies:
 * - Returns paginated events for administrators (all events)
 * - Returns paginated events for moderators (only moderated organizations)
 * - Filters events by EventFilter criteria
 * - Includes event shift counts
 * - Orders events by event_end date descending
 * - Preserves query string in pagination
 * - Respects page size parameter
 */
class GetEventsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_length_aware_paginator(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $trooper = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        // Act
        $result = $subject($trooper, $filter);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_invoke_returns_all_events_for_administrator(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        $organization = Organization::factory()->create();
        Event::factory()->count(3)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertEquals(3, $result->total());
    }

    public function test_invoke_returns_only_moderated_events_for_moderator(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $moderator = Trooper::factory()->asModerator()->create();
        $filter = new EventFilter(new Request());

        // Organization the moderator has access to
        $moderated_org = Organization::factory()->create();
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Organization the moderator does NOT have access to
        $other_org = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $moderated_org->id,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $other_org->id,
        ]);

        // Act
        $result = $subject($moderator, $filter);

        // Assert
        $this->assertEquals(2, $result->total());
    }

    public function test_invoke_filters_by_event_status(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
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

        $request = new Request(['status' => EventStatus::OPEN->value]);
        $filter = new EventFilter($request);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertEquals(2, $result->total());
    }

    public function test_invoke_filters_by_organization(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Event::factory()->count(2)->create([
            Event::ORGANIZATION_ID => $org1->id,
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org2->id,
        ]);

        $request = new Request(['organization_id' => $org1->id]);
        $filter = new EventFilter($request);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertEquals(2, $result->total());
    }

    public function test_invoke_respects_page_size_parameter(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        $organization = Organization::factory()->create();
        Event::factory()->count(25)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $result = $subject($admin, $filter, 10);

        // Assert
        $this->assertEquals(25, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertCount(10, $result->items());
    }

    public function test_invoke_uses_default_page_size_of_15(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        $organization = Organization::factory()->create();
        Event::factory()->count(20)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertEquals(15, $result->perPage());
        $this->assertCount(15, $result->items());
    }

    public function test_invoke_orders_by_event_end_descending(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

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

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $items = $result->items();
        $this->assertEquals($event2->id, $items[0]->id); // Most recent end date first
        $this->assertEquals($event3->id, $items[1]->id);
        $this->assertEquals($event1->id, $items[2]->id);
    }

    public function test_invoke_includes_event_shifts_count(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Create 3 event shifts manually
        \App\Models\EventShift::factory()->count(3)->create([
            \App\Models\EventShift::EVENT_ID => $event->id,
        ]);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertCount(1, $result->items());
        $retrieved_event = $result->items()[0];
        $this->assertEquals(3, $retrieved_event->event_shifts_count);
    }

    public function test_invoke_returns_empty_paginator_when_no_events(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertCount(0, $result->items());
    }

    public function test_invoke_moderator_sees_no_events_without_assignments(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $moderator = Trooper::factory()->asModerator()->create();
        $filter = new EventFilter(new Request());

        $organization = Organization::factory()->create();
        Event::factory()->count(3)->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $result = $subject($moderator, $filter);

        // Assert
        $this->assertEquals(0, $result->total());
    }

    public function test_invoke_includes_organization_relationship(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();
        $filter = new EventFilter(new Request());

        $organization = Organization::factory()->create([
            Organization::NAME => 'Florida Garrison',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $event = $result->items()[0];
        $this->assertTrue($event->relationLoaded('organization'));
        $this->assertEquals('Florida Garrison', $event->organization->name);
    }

    public function test_invoke_filters_by_search_term(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
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

        $request = new Request(['search_term' => 'Star Wars']);
        $filter = new EventFilter($request);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('Star Wars', $result->items()[0]->name);
    }

    public function test_invoke_handles_multiple_filter_criteria(): void
    {
        // Arrange
        $subject = new GetEventsQuery();
        $admin = Trooper::factory()->asAdministrator()->create();

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Event::factory()->create([
            Event::ORGANIZATION_ID => $org1->id,
            Event::STATUS => EventStatus::OPEN,
            Event::NAME => 'Target Event',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org1->id,
            Event::STATUS => EventStatus::CLOSED,
            Event::NAME => 'Target Event 2',
        ]);
        Event::factory()->create([
            Event::ORGANIZATION_ID => $org2->id,
            Event::STATUS => EventStatus::OPEN,
            Event::NAME => 'Other Event',
        ]);

        $request = new Request([
            'organization_id' => $org1->id,
            'status' => EventStatus::OPEN->value,
            'search_term' => 'Target',
        ]);
        $filter = new EventFilter($request);

        // Act
        $result = $subject($admin, $filter);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals('Target Event', $result->items()[0]->name);
    }
}
