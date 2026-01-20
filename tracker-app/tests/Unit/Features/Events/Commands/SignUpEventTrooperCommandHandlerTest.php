<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Commands\SignUpEventTrooperCommandHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for SignUpEventTrooperCommandHandler.
 *
 * Verifies:
 * - Creates EventTrooper record with correct attributes
 * - Tracks who added the signup
 * - Sets timestamp appropriately
 */
class SignUpEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_trooper(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();

        $trooper = Trooper::factory()->create();
        $added_by_trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand($shift, $trooper, $added_by_trooper, null);
        $subject = new SignUpEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertInstanceOf(EventTrooper::class, $result);
        $this->assertEquals($shift->id, $result->event_shift_id);
        $this->assertEquals($trooper->id, $result->trooper_id);
    }

    public function test_invoke_sets_added_by_trooper_when_different(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();

        $trooper = Trooper::factory()->create();
        $added_by_trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand($shift, $trooper, $added_by_trooper, null);
        $subject = new SignUpEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertEquals($added_by_trooper->id, $result->added_by_trooper_id);
    }

    public function test_invoke_sets_added_by_trooper_null_when_self_signup(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();

        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand($shift, $trooper, $trooper, null);
        $subject = new SignUpEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result->added_by_trooper_id);
    }

    public function test_invoke_sets_signed_up_at_timestamp(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();

        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand($shift, $trooper, $trooper, null);
        $subject = new SignUpEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNotNull($result->signed_up_at);
    }

    public function test_invoke_returns_event_trooper(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();

        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand($shift, $trooper, $trooper, null);
        $subject = new SignUpEventTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertInstanceOf(EventTrooper::class, $result);
        $this->assertTrue($result->exists);
    }
}
