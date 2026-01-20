<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Features\Events\Queries\GetEventDisplayQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetEventDisplayQueryHandler.
 *
 * Verifies:
 * - Returns event with all related data loaded
 * - Eager loads relationships properly
 * - Assembles event for display
 */
class GetEventDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_event_with_relationships(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertInstanceOf(Event::class, $result);
        $this->assertEquals($event->id, $result->id);
    }

    public function test_invoke_eager_loads_organization(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->relationLoaded('organization'));
    }

    public function test_invoke_eager_loads_event_shifts(): void
    {
        // Arrange
        $event = Event::factory()->create();
        EventShift::factory()->for($event)->count(2)->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->relationLoaded('event_shifts'));
        $this->assertCount(2, $result->event_shifts);
    }

    public function test_invoke_orders_shifts_by_start_time(): void
    {
        // Arrange
        $event = Event::factory()->create();

        $later_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addHours(2),
        ]);
        $earlier_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addHour(),
        ]);

        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals($earlier_shift->id, $result->event_shifts->first()->id);
        $this->assertEquals($later_shift->id, $result->event_shifts->last()->id);
    }

    public function test_invoke_eager_loads_event_troopers(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $shift_result = $result->event_shifts->first();
        $this->assertTrue($shift_result->relationLoaded('event_troopers'));
    }
}
