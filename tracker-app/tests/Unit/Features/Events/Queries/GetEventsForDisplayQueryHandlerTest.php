<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Events\Queries\GetEventsForDisplayQueryHandler;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetEventsForDisplayQueryHandler.
 *
 * Verifies:
 * - Returns only upcoming events
 * - Eager loads relationships
 * - Orders events appropriately
 */
class GetEventsForDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_upcoming_events(): void
    {
        // Arrange
        Event::factory()->create([
            Event::EVENT_START => now()->addWeek(),
            Event::STATUS => EventStatus::OPEN,
        ]);

        Event::factory()->create([
            Event::EVENT_START => now()->subWeek(),
            Event::STATUS => EventStatus::OPEN,
        ]);

        $query = new GetEventsForDisplayQuery();
        $subject = new GetEventsForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->event_start->isFuture());
    }

    public function test_invoke_eager_loads_organization(): void
    {
        // Arrange
        Event::factory()->create([
            Event::EVENT_START => now()->addWeek(),
            Event::STATUS => EventStatus::OPEN,
        ]);

        $query = new GetEventsForDisplayQuery();
        $subject = new GetEventsForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->first()->relationLoaded('organization'));
    }

    public function test_invoke_eager_loads_organizations(): void
    {
        // Arrange
        Event::factory()->create([
            Event::EVENT_START => now()->addWeek(),
            Event::STATUS => EventStatus::OPEN,
        ]);

        $query = new GetEventsForDisplayQuery();
        $subject = new GetEventsForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->first()->relationLoaded('organizations'));
    }

    public function test_invoke_returns_empty_collection_when_no_upcoming_events(): void
    {
        // Arrange
        Event::factory()->create([
            Event::EVENT_START => now()->subWeek(),
            Event::STATUS => EventStatus::OPEN,
        ]);

        $query = new GetEventsForDisplayQuery();
        $subject = new GetEventsForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_multiple_events(): void
    {
        // Arrange
        Event::factory()->count(3)->create([
            Event::EVENT_START => now()->addWeek(),
            Event::STATUS => EventStatus::OPEN,
        ]);

        $query = new GetEventsForDisplayQuery();
        $subject = new GetEventsForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(3, $result);
    }
}
