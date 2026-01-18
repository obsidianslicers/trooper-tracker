<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\GetTroopersForEventCancelledQuery;
use App\Features\Events\Queries\GetTroopersForEventCancelledQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventCancelledQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_troopers_with_going_status(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $query = new GetTroopersForEventCancelledQuery($event);
        $subject = new GetTroopersForEventCancelledQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($trooper->id, $result->first()->id);
    }

    public function test_invoke_excludes_troopers_with_non_going_status(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);

        $query = new GetTroopersForEventCancelledQuery($event);
        $subject = new GetTroopersForEventCancelledQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_only_returns_active_troopers(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
        ]);

        $retired_trooper = Trooper::factory()->asRetired()->create();

        EventTrooper::factory()->create([
            EventTrooper::TROOPER_ID => $retired_trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $query = new GetTroopersForEventCancelledQuery($event);
        $subject = new GetTroopersForEventCancelledQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_filters_by_event_id(): void
    {
        // Arrange
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();

        $shift1 = EventShift::factory()->create([
            EventShift::EVENT_ID => $event1->id,
        ]);
        $shift2 = EventShift::factory()->create([
            EventShift::EVENT_ID => $event2->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $query = new GetTroopersForEventCancelledQuery($event1);
        $subject = new GetTroopersForEventCancelledQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_empty_collection_when_no_signups(): void
    {
        // Arrange
        $event = Event::factory()->create();

        $query = new GetTroopersForEventCancelledQuery($event);
        $subject = new GetTroopersForEventCancelledQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }
}