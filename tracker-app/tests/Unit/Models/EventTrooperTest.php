<?php

namespace Tests\Unit\Models;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_status_returns_true_when_shift_is_open_and_trooper_has_ownership_and_status_is_going(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_update_status_returns_true_when_shift_is_open_and_added_by_trooper_has_ownership(): void
    {
        // Arrange
        $owner_trooper = Trooper::factory()->create();
        $assigned_trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $assigned_trooper->id,
            'added_by_trooper_id' => $owner_trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $owner_trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_update_status_returns_false_when_shift_is_not_open(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->closed()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_update_status_returns_false_when_trooper_does_not_have_ownership(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $different_trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $different_trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_update_status_returns_false_when_status_not_going_and_troopers_maxed_and_not_handler(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create(['troopers_allowed' => 0]);
        $event_shift = EventShift::factory()->create(['event_id' => $event->id]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::TENTATIVE,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_update_status_returns_false_when_status_not_going_and_handlers_maxed_and_is_handler(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create(['handlers_allowed' => 0]);
        $event_shift = EventShift::factory()->create(['event_id' => $event->id]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::TENTATIVE,
            'is_handler' => true,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_update_status_returns_true_when_status_not_going_and_troopers_not_maxed_and_not_handler(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::TENTATIVE,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_update_status_returns_true_when_status_not_going_and_handlers_not_maxed_and_is_handler(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::TENTATIVE,
            'is_handler' => true,
        ]);

        // Act
        $result = $event_trooper->canUpdateStatus($event_shift, $trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_true_when_shift_is_open_and_not_handler_and_trooper_has_ownership(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateCostume($event_shift, $trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_true_when_shift_is_open_and_not_handler_and_added_by_trooper_has_ownership(): void
    {
        // Arrange
        $owner_trooper = Trooper::factory()->create();
        $assigned_trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $assigned_trooper->id,
            'added_by_trooper_id' => $owner_trooper->id,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateCostume($event_shift, $owner_trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_false_when_is_handler(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'is_handler' => true,
        ]);

        // Act
        $result = $event_trooper->canUpdateCostume($event_shift, $trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_update_costume_returns_false_when_shift_is_not_open(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->closed()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateCostume($event_shift, $trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_update_costume_returns_false_when_trooper_does_not_have_ownership(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $different_trooper = Trooper::factory()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'is_handler' => false,
        ]);

        // Act
        $result = $event_trooper->canUpdateCostume($event_shift, $different_trooper);

        // Assert
        $this->assertFalse($result);
    }
}
