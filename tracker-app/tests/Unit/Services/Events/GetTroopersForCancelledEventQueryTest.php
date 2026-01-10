<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\Events\GetTroopersForCancelledEventQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the GetTroopersForCancelledEventQuery service.
 *
 * Validates that the service correctly retrieves troopers who
 * signed up for a cancelled event with GOING status.
 */
class GetTroopersForCancelledEventQueryTest extends TestCase
{
    use RefreshDatabase;

    private GetTroopersForCancelledEventQuery $subject;
    private Event $event;
    private EventShift $event_shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GetTroopersForCancelledEventQuery();
        $this->event = Event::factory()->create();
        $this->event_shift = EventShift::factory()->for($this->event)->create();
    }

    public function test_invoke_returns_trooper_with_going_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($trooper));
    }

    public function test_invoke_does_not_return_trooper_with_tentative_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::TENTATIVE,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_does_not_return_trooper_with_unable_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::UNABLE_TO_ATTEND,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_only_returns_active_troopers(): void
    {
        // Arrange
        $active_trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
        ]);
        $inactive_trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::PENDING,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $active_trooper->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $inactive_trooper->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($active_trooper));
        $this->assertFalse($result->contains($inactive_trooper));
    }

    public function test_invoke_returns_empty_collection_when_no_troopers_signed_up(): void
    {
        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_troopers_from_multiple_shifts(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);
        $trooper2 = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);

        $shift1 = EventShift::factory()->for($this->event)->create();
        $shift2 = EventShift::factory()->for($this->event)->create();

        EventTrooper::factory()->create([
            'trooper_id' => $trooper1->id,
            'event_shift_id' => $shift1->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper2->id,
            'event_shift_id' => $shift2->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($trooper1));
        $this->assertTrue($result->contains($trooper2));
    }

    public function test_invoke_does_not_return_troopers_from_different_event(): void
    {
        // Arrange
        $other_event = Event::factory()->create();
        $other_shift = EventShift::factory()->for($other_event)->create();

        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $other_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_same_trooper_only_once_when_signed_up_for_multiple_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);

        $shift1 = EventShift::factory()->for($this->event)->create();
        $shift2 = EventShift::factory()->for($this->event)->create();

        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift1->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift2->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($trooper));
    }

    public function test_invoke_returns_multiple_active_troopers_with_going_status(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);
        $trooper2 = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);
        $trooper3 = Trooper::factory()->create(['membership_status' => MembershipStatus::ACTIVE]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper1->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper2->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        EventTrooper::factory()->create([
            'trooper_id' => $trooper3->id,
            'event_shift_id' => $this->event_shift->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains($trooper1));
        $this->assertTrue($result->contains($trooper2));
        $this->assertTrue($result->contains($trooper3));
    }
}
