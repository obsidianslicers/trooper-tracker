<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
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

    public function test_can_update_trooper_status_returns_true_for_active_events(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->make([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::now()->subDays(90),
        ]);

        // Act
        $can_update = $event->can_update_trooper_status;

        // Assert
        $this->assertTrue($can_update);

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_can_update_trooper_status_returns_true_within_30_days_after_event_end(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->make([
            'status' => EventStatus::CLOSED,
            'event_end' => Carbon::now()->subDays(10),
        ]);

        // Act
        $can_update = $event->can_update_trooper_status;

        // Assert
        $this->assertTrue($can_update);

        // Cleanup
        Carbon::setTestNow();
    }

    public function test_can_update_trooper_status_returns_false_after_30_days_for_inactive_events(): void
    {
        // Arrange
        Carbon::setTestNow('2026-10-01 12:00:00');
        $event = Event::factory()->make([
            'status' => EventStatus::CLOSED,
            'event_end' => Carbon::now()->subDays(31),
        ]);

        // Act
        $can_update = $event->can_update_trooper_status;

        // Assert
        $this->assertFalse($can_update);

        // Cleanup
        Carbon::setTestNow();
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

    public function test_get_shift_count_for_returns_zero_when_trooper_not_signed_up_for_any_shifts(): void
    {
        $subject = Event::factory()->open()->create();
        $event_shift_1 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_2 = EventShift::factory()->for($subject)->open()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(0, $result);
    }

    public function test_get_shift_count_for_returns_one_when_trooper_signed_up_for_one_shift(): void
    {
        $subject = Event::factory()->open()->create();
        $event_shift_1 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_2 = EventShift::factory()->for($subject)->open()->create();
        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(1, $result);
    }

    public function test_get_shift_count_for_returns_multiple_when_trooper_signed_up_for_multiple_shifts(): void
    {
        $subject = Event::factory()->open()->create();
        $event_shift_1 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_2 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_3 = EventShift::factory()->for($subject)->open()->create();
        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(2, $result);
    }

    public function test_get_shift_count_for_counts_all_shifts_regardless_of_status(): void
    {
        $subject = Event::factory()->open()->create();
        $event_shift_1 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_2 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_3 = EventShift::factory()->for($subject)->open()->create();
        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_3->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(3, $result);
    }

    public function test_get_shift_count_for_only_counts_shifts_for_specified_trooper(): void
    {
        $subject = Event::factory()->open()->create();
        $event_shift_1 = EventShift::factory()->for($subject)->open()->create();
        $event_shift_2 = EventShift::factory()->for($subject)->open()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift_2->id,
            EventTrooper::TROOPER_ID => $other_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(1, $result);
    }

    public function test_get_shift_count_for_works_with_unloaded_relationships(): void
    {
        $subject = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->for($subject)->open()->create();
        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        // Refresh to clear loaded relationships
        $subject = Event::find($subject->id);

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(1, $result);
    }

    public function test_get_shift_count_for_returns_zero_for_event_with_no_shifts(): void
    {
        $subject = Event::factory()->open()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(0, $result);
    }
}