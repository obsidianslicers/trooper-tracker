<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommandHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PromoteNextInLineEventTrooperCommandHandler.
 *
 * Verifies:
 * - Returns null (handler orchestrates other actions)
 */
class PromoteNextInLineEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_null(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $event_trooper = EventTrooper::factory()
            ->for($shift, 'event_shift')
            ->for($trooper, 'trooper')
            ->create();

        $command = new PromoteNextInLineEventTrooperCommand($event_trooper);
        $subject = new PromoteNextInLineEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
    }
}
