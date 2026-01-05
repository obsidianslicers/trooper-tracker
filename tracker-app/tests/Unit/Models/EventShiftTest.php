<?php

namespace Tests\Unit\Models;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\EventShift
 */
class EventShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_open_attribute_returns_true_when_shift_and_event_are_open(): void
    {
        // Arrange
        $event = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->open()->for($event)->create();

        // Act & Assert
        $this->assertTrue($event_shift->is_open);
    }

    public function test_is_open_attribute_returns_false_when_event_is_not_open(): void
    {
        // Arrange
        $event = Event::factory()->closed()->create();
        $event_shift = EventShift::factory()->open()->create(['event_id' => $event->id]);

        // Act & Assert
        $this->assertFalse($event_shift->is_open);
    }

    public function test_is_open_attribute_returns_false_when_shift_is_not_open(): void
    {
        // Arrange
        $event = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->closed()->for($event)->create();

        // Act & Assert
        $this->assertFalse($event_shift->is_open);
    }

    public function test_is_locked_attribute_returns_true_when_event_is_locked(): void
    {
        // Arrange
        $event = Event::factory()->create(['status' => \App\Enums\EventStatus::SIGN_UP_LOCKED]);
        $event_shift = EventShift::factory()->open()->for($event)->create();

        // Act & Assert
        $this->assertTrue($event_shift->is_locked);
    }

    public function test_is_locked_attribute_returns_true_when_shift_is_locked(): void
    {
        // Arrange
        $event = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->for($event)->create(['status' => \App\Enums\EventStatus::SIGN_UP_LOCKED]);

        // Act & Assert
        $this->assertTrue($event_shift->is_locked);
    }

    public function test_is_locked_attribute_returns_false_when_neither_locked(): void
    {
        // Arrange
        $event = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->open()->for($event)->create();

        // Act & Assert
        $this->assertFalse($event_shift->is_locked);
    }

    public function test_time_display_attribute_formats_shift_times_correctly(): void
    {
        // Arrange
        $event_shift = EventShift::factory()->make([
            'shift_starts_at' => '2026-10-03 14:00:00',
            'shift_ends_at' => '2026-10-03 16:00:00',
        ]);

        // Act
        $time_display = $event_shift->time_display;

        // Assert
        $this->assertSame('Sat - Oct 03, 2026 - 2:00pm - 4:00pm', $time_display);
    }

    public function test_short_time_display_attribute_formats_shift_times_correctly(): void
    {
        // Arrange
        $event_shift = EventShift::factory()->make([
            'shift_starts_at' => '2026-10-03 14:00:00',
            'shift_ends_at' => '2026-10-03 16:00:00',
        ]);

        // Act
        $short_time_display = $event_shift->short_time_display;

        // Assert
        $this->assertSame('10/03 - 2:00 pm - 4:00pm', $short_time_display);
    }

    public function test_is_going_returns_true_when_trooper_is_going(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->isGoing($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_going_returns_false_when_trooper_is_not_going(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::TENTATIVE,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->isGoing($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_is_going_returns_false_when_trooper_not_signed_up(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->isGoing($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_troopers_maxed_returns_true_when_troopers_at_capacity(): void
    {
        // Arrange
        $event = Event::factory()->create(['troopers_allowed' => 2]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(2)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => false,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->troopersMaxed();

        // Assert
        $this->assertTrue($result);
    }

    public function test_troopers_maxed_returns_false_when_under_capacity(): void
    {
        // Arrange
        $event = Event::factory()->create(['troopers_allowed' => 5]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(2)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => false,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->troopersMaxed();

        // Assert
        $this->assertFalse($result);
    }

    public function test_troopers_maxed_returns_false_when_troopers_allowed_is_null(): void
    {
        // Arrange
        $event = Event::factory()->create(['troopers_allowed' => null]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(10)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => false,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->troopersMaxed();

        // Assert
        $this->assertFalse($result);
    }

    public function test_troopers_maxed_returns_true_when_troopers_exceed_capacity(): void
    {
        // Arrange
        $event = Event::factory()->create(['troopers_allowed' => 2]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(3)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => false,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->troopersMaxed();

        // Assert
        $this->assertTrue($result);
    }

    public function test_handlers_maxed_returns_true_when_handlers_at_capacity(): void
    {
        // Arrange
        $event = Event::factory()->create(['handlers_allowed' => 2]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(2)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => true,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->handlersMaxed();

        // Assert
        $this->assertTrue($result);
    }

    public function test_handlers_maxed_returns_false_when_under_capacity(): void
    {
        // Arrange
        $event = Event::factory()->create(['handlers_allowed' => 5]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(2)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => true,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->handlersMaxed();

        // Assert
        $this->assertFalse($result);
    }

    public function test_handlers_maxed_returns_false_when_handlers_allowed_is_null(): void
    {
        // Arrange
        $event = Event::factory()->create(['handlers_allowed' => null]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(10)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => true,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->handlersMaxed();

        // Assert
        $this->assertFalse($result);
    }

    public function test_handlers_maxed_returns_true_when_handlers_exceed_capacity(): void
    {
        // Arrange
        $event = Event::factory()->create(['handlers_allowed' => 2]);
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->count(3)->create([
            'event_shift_id' => $event_shift->id,
            'costume_id' => $costume->id,
            'is_handler' => true,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $result = $event_shift->handlersMaxed();

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_signed_up_returns_true_when_trooper_is_signed_up(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->isSignedUp($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_signed_up_returns_false_when_trooper_is_not_signed_up(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->isSignedUp($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_true_when_shift_is_open_and_trooper_not_signed_up(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->open()->for($event)->create();

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUp($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_sign_up_returns_false_when_shift_is_open_but_trooper_already_signed_up(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->open()->create();
        $event_shift = EventShift::factory()->open()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUp($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_false_when_shift_is_closed(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->closed()->create();
        $event_shift = EventShift::factory()->closed()->for($event)->create();

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUp($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_sign_up_friend_returns_true_when_trooper_signed_up_and_under_friend_limit(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->open()->create(['friends_allowed' => 2]);
        $event_shift = EventShift::factory()->open()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUpFriend($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_sign_up_friend_returns_false_when_trooper_not_signed_up(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->open()->create(['friends_allowed' => 2]);
        $event_shift = EventShift::factory()->open()->for($event)->create();

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUpFriend($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_sign_up_friend_returns_false_when_shift_is_closed(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->closed()->create(['friends_allowed' => 2]);
        $event_shift = EventShift::factory()->closed()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUpFriend($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_can_sign_up_friend_returns_true_when_friends_allowed_is_null(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->open()->create(['friends_allowed' => null]);
        $event_shift = EventShift::factory()->open()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUpFriend($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_sign_up_friend_returns_false_when_at_friend_limit(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $friend = Trooper::factory()->create();
        $event = Event::factory()->open()->create(['friends_allowed' => 1]);
        $event_shift = EventShift::factory()->open()->for($event)->create();
        $costume = OrganizationCostume::factory()->create();

        // Trooper is signed up
        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        // Friend added by trooper (at limit)
        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $friend->id,
            'added_by_trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        $event_shift->load('event_troopers');

        // Act
        $result = $event_shift->canSignUpFriend($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_calendar_link_returns_link_object(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'venue_address' => '123 Main St',
        ]);
        $event_shift = EventShift::factory()->for($event)->create();

        // Act
        $result = $event_shift->createCalendarLink();

        // Assert
        $this->assertInstanceOf(\Spatie\CalendarLinks\Link::class, $result);
    }
}
