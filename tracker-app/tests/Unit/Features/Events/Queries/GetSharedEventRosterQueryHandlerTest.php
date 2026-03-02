<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\GetSharedEventRosterQuery;
use App\Features\Events\Queries\GetSharedEventRosterQueryHandler;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetSharedEventRosterQueryHandler.
 *
 * Verifies:
 * - Returns event with all related data loaded for shared rosters
 * - Eager loads relationships properly
 * - Filters to only GOING status troopers
 * - Orders troopers by legal name
 */
class GetSharedEventRosterQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_event_with_relationships(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertInstanceOf(Event::class, $result);
        $this->assertEquals($event->id, $result->id);
    }

    public function test_invoke_eager_loads_organization(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->relationLoaded('organization'));
    }

    public function test_invoke_eager_loads_event_shifts(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);
        EventShift::factory()->for($event)->count(2)->create();

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->relationLoaded('event_shifts'));
        $this->assertCount(2, $result->event_shifts);
    }

    public function test_invoke_orders_shifts_by_start_time(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);

        $later_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addHours(2),
        ]);
        $earlier_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addHour(),
        ]);

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals($earlier_shift->id, $result->event_shifts->first()->id);
        $this->assertEquals($later_shift->id, $result->event_shifts->last()->id);
    }

    public function test_invoke_eager_loads_event_troopers(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->event_shifts->first()->relationLoaded('event_troopers'));
    }

    public function test_invoke_filters_to_only_going_status_troopers(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();

        $going_trooper = Trooper::factory()->create();
        $cancelled_trooper = Trooper::factory()->create();
        $standby_trooper = Trooper::factory()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $going_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $cancelled_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $standby_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
        ]);

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $event_troopers = $result->event_shifts->first()->event_troopers;
        $this->assertCount(1, $event_troopers);
        $this->assertEquals($going_trooper->id, $event_troopers->first()->trooper_id);
    }

    public function test_invoke_orders_troopers_by_legal_name(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();

        $trooper_z = Trooper::factory()->create([
            Trooper::LEGAL_NAME => 'Zach Anderson',
        ]);
        $trooper_a = Trooper::factory()->create([
            Trooper::LEGAL_NAME => 'Alice Baker',
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper_z->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
            EventTrooper::SIGNED_UP_AT => now(),
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper_a->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
            EventTrooper::SIGNED_UP_AT => now()->addMinutes(5),
        ]);

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $event_troopers = $result->event_shifts->first()->event_troopers;
        $this->assertEquals('Alice Baker', $event_troopers->first()->trooper->legal_name);
        $this->assertEquals('Zach Anderson', $event_troopers->last()->trooper->legal_name);
    }

    public function test_invoke_eager_loads_trooper_relationships(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $event_share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $query = new GetSharedEventRosterQuery($event_share);
        $subject = new GetSharedEventRosterQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $event_trooper = $result->event_shifts->first()->event_troopers->first();
        $this->assertTrue($event_trooper->relationLoaded('trooper'));
        $this->assertTrue($event_trooper->relationLoaded('costume'));
    }
}
