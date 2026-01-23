<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Enums\EventTrooperStatus;
use App\Features\Troopers\Queries\GetMostRecentEventsForTrooperQuery;
use App\Features\Troopers\Queries\GetMostRecentEventsForTrooperQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMostRecentEventsForTrooperQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_organizations_with_most_recent_attended_event_shift(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $event_older = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
        ]);
        $shift_older = EventShift::factory()->for($event_older)->create([
            'shift_starts_at' => now()->subDays(10),
        ]);
        EventTrooper::factory()->create([
            'event_shift_id' => $shift_older->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::ATTENDED,
        ]);

        $event_recent = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
        ]);
        $shift_recent = EventShift::factory()->for($event_recent)->create([
            'shift_starts_at' => now()->subDays(5),
        ]);
        EventTrooper::factory()->create([
            'event_shift_id' => $shift_recent->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetMostRecentEventsForTrooperQuery($trooper);
        $subject = new GetMostRecentEventsForTrooperQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());
        $organization_result = $result->firstWhere('id', $organization->id);
        $this->assertNotNull($organization_result);
        $this->assertNotNull($organization_result->event_shift);
        $this->assertEquals($shift_recent->id, $organization_result->event_shift->id);
    }

    public function test_invoke_only_returns_attended_status_events(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $event_going = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
        ]);
        $shift_going = EventShift::factory()->for($event_going)->create([
            'shift_starts_at' => now()->subDays(5),
        ]);
        EventTrooper::factory()->create([
            'event_shift_id' => $shift_going->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        $event_attended = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::PRIMARY_ORGANIZATION_ID => $organization->id,
        ]);
        $shift_attended = EventShift::factory()->for($event_attended)->create([
            'shift_starts_at' => now()->subDays(10),
        ]);
        EventTrooper::factory()->create([
            'event_shift_id' => $shift_attended->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetMostRecentEventsForTrooperQuery($trooper);
        $subject = new GetMostRecentEventsForTrooperQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $organization_result = $result->firstWhere('id', $organization->id);
        $this->assertNotNull($organization_result);
        $this->assertNotNull($organization_result->event_shift);
        $this->assertEquals($shift_attended->id, $organization_result->event_shift->id);
    }

    public function test_invoke_sets_event_shift_to_null_when_no_attended_events(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $query = new GetMostRecentEventsForTrooperQuery($trooper);
        $subject = new GetMostRecentEventsForTrooperQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());
        $organization_result = $result->firstWhere('id', $organization->id);
        $this->assertNotNull($organization_result);
        $this->assertNull($organization_result->event_shift);
    }

    public function test_invoke_groups_events_by_primary_organization(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org_1 = Organization::factory()->create();
        $org_2 = Organization::factory()->create();

        $event_org_1 = Event::factory()->create([
            Event::ORGANIZATION_ID => $org_1->id,
            Event::PRIMARY_ORGANIZATION_ID => $org_1->id,
        ]);
        $shift_org_1 = EventShift::factory()->for($event_org_1)->create([
            'shift_starts_at' => now()->subDays(5),
        ]);
        EventTrooper::factory()->create([
            'event_shift_id' => $shift_org_1->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::ATTENDED,
        ]);

        $event_org_2 = Event::factory()->create([
            Event::ORGANIZATION_ID => $org_2->id,
            Event::PRIMARY_ORGANIZATION_ID => $org_2->id,
        ]);
        $shift_org_2 = EventShift::factory()->for($event_org_2)->create([
            'shift_starts_at' => now()->subDays(3),
        ]);
        EventTrooper::factory()->create([
            'event_shift_id' => $shift_org_2->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetMostRecentEventsForTrooperQuery($trooper);
        $subject = new GetMostRecentEventsForTrooperQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());

        $org_1_result = $result->firstWhere('id', $org_1->id);
        $this->assertNotNull($org_1_result);
        $this->assertNotNull($org_1_result->event_shift);
        $this->assertEquals($shift_org_1->id, $org_1_result->event_shift->id);

        $org_2_result = $result->firstWhere('id', $org_2->id);
        $this->assertNotNull($org_2_result);
        $this->assertNotNull($org_2_result->event_shift);
        $this->assertEquals($shift_org_2->id, $org_2_result->event_shift->id);
    }

    public function test_invoke_returns_all_organizations_even_without_events(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org_with_events = Organization::factory()->create();
        $org_without_events = Organization::factory()->create();

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $org_with_events->id,
            Event::PRIMARY_ORGANIZATION_ID => $org_with_events->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();
        EventTrooper::factory()->create([
            'event_shift_id' => $shift->id,
            'trooper_id' => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetMostRecentEventsForTrooperQuery($trooper);
        $subject = new GetMostRecentEventsForTrooperQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());

        $org_with_result = $result->firstWhere('id', $org_with_events->id);
        $this->assertNotNull($org_with_result);
        $this->assertNotNull($org_with_result->event_shift);

        $org_without_result = $result->firstWhere('id', $org_without_events->id);
        $this->assertNotNull($org_without_result);
        $this->assertNull($org_without_result->event_shift);
    }

    public function test_invoke_only_returns_organization_type_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $organization->id,
        ]);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $region->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();
        EventTrooper::factory()->create([
            'event_shift_id' => $shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::ATTENDED,
        ]);

        $query = new GetMostRecentEventsForTrooperQuery($trooper);
        $subject = new GetMostRecentEventsForTrooperQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());

        // The organization should have the event_shift because EventObserver
        // sets primary_organization_id to the parent club when event belongs to a region
        $organization_result = $result->firstWhere('id', $organization->id);
        $this->assertNotNull($organization_result);
        $this->assertNotNull($organization_result->event_shift);

        // Verify region is not in the results (only organization types)
        $region_result = $result->firstWhere('id', $region->id);
        $this->assertNull($region_result);
    }
}
