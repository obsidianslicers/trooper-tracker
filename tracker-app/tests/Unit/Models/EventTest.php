<?php

namespace Tests\Unit\Models;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\Event
 */
class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_display_attribute_formats_event_times_correctly(): void
    {
        // Arrange
        $event = Event::factory()->make([
            'event_start' => '2026-10-03 14:00:00',
            'event_end' => '2026-10-03 16:00:00',
        ]);

        // Act
        $time_display = $event->time_display;

        // Assert
        $this->assertSame('Sat - Oct 03, 2026 - 2:00pm - 4:00pm', $time_display);
    }

    public function test_is_open_attribute_returns_true_when_status_is_open(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::OPEN]);

        // Act & Assert
        $this->assertTrue($event->is_open);
    }

    public function test_is_open_attribute_returns_false_when_status_is_not_open(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::DRAFT]);

        // Act & Assert
        $this->assertFalse($event->is_open);
    }

    public function test_is_locked_attribute_returns_true_when_status_is_sign_up_locked(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::SIGN_UP_LOCKED]);

        // Act & Assert
        $this->assertTrue($event->is_locked);
    }

    public function test_is_locked_attribute_returns_false_when_status_is_not_locked(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::OPEN]);

        // Act & Assert
        $this->assertFalse($event->is_locked);
    }

    public function test_is_draft_attribute_returns_true_when_status_is_draft(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::DRAFT]);

        // Act & Assert
        $this->assertTrue($event->is_draft);
    }

    public function test_is_draft_attribute_returns_false_when_status_is_not_draft(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::OPEN]);

        // Act & Assert
        $this->assertFalse($event->is_draft);
    }

    public function test_is_active_attribute_returns_true_when_status_is_draft(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::DRAFT]);

        // Act & Assert
        $this->assertTrue($event->is_active);
    }

    public function test_is_active_attribute_returns_true_when_status_is_open(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::OPEN]);

        // Act & Assert
        $this->assertTrue($event->is_active);
    }

    public function test_is_active_attribute_returns_true_when_status_is_sign_up_locked(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::SIGN_UP_LOCKED]);

        // Act & Assert
        $this->assertTrue($event->is_active);
    }

    public function test_is_active_attribute_returns_false_when_status_is_closed(): void
    {
        // Arrange
        $event = Event::factory()->make(['status' => EventStatus::CLOSED]);

        // Act & Assert
        $this->assertFalse($event->is_active);
    }

    public function test_at_risk_attribute_returns_true_when_event_starts_soon_with_no_troopers(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_start' => '2026-10-05 14:00:00', // 4 days away
        ]);
        $event_shift = EventShift::factory()->create(['event_id' => $event->id]);

        // Act
        $at_risk = $event->fresh()->at_risk;

        // Assert
        $this->assertTrue($at_risk);

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_at_risk_attribute_returns_false_when_event_starts_soon_with_troopers(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_start' => '2026-10-05 14:00:00', // 4 days away
        ]);
        $event_shift = EventShift::factory()->create(['event_id' => $event->id]);
        \App\Models\EventTrooper::factory()->create(['event_shift_id' => $event_shift->id]);

        // Act - Load event_shifts with event_troopers count
        $event_loaded = $event->fresh()->load(['event_shifts' => function ($query)
        {
            $query->withCount('event_troopers');
        }]);
        $at_risk = $event_loaded->at_risk;

        // Assert
        $this->assertFalse($at_risk);

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_at_risk_attribute_returns_false_when_event_starts_later(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_start' => '2026-10-10 14:00:00', // 9 days away
        ]);
        $event_shift = EventShift::factory()->create(['event_id' => $event->id]);

        // Act
        $at_risk = $event->fresh()->at_risk;

        // Assert
        $this->assertFalse($at_risk);

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_at_risk_attribute_returns_false_when_event_is_not_active(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->create([
            'status' => EventStatus::CLOSED,
            'event_start' => '2026-10-05 14:00:00', // 4 days away
        ]);
        $event_shift = EventShift::factory()->create(['event_id' => $event->id]);

        // Act
        $at_risk = $event->fresh()->at_risk;

        // Assert
        $this->assertFalse($at_risk);

        // Cleanup
        Carbon::setTestNow();
    }
}