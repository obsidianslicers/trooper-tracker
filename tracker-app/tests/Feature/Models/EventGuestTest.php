<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventGuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_guest(): void
    {
        $subject = EventGuest::factory()->create();

        $this->assertInstanceOf(EventGuest::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            EventGuest::ID => $subject->getKey(),
        ]);
    }

    public function test_added_by_trooper_relationship_returns_belongs_to(): void
    {
        $subject = EventGuest::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->added_by_trooper());
    }

    public function test_status_is_cast_to_event_guest_status_enum(): void
    {
        $subject = EventGuest::factory()->state([
            EventGuest::STATUS => EventGuestStatus::CANCELLED,
        ])->create();

        $this->assertInstanceOf(EventGuestStatus::class, $subject->status);
        $this->assertSame(EventGuestStatus::CANCELLED, $subject->status);
    }

    public function test_can_update_status_returns_true_when_shift_open_and_owner(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $subject = EventGuest::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        $this->assertTrue($subject->canUpdateStatus($trooper));
    }

    public function test_can_update_status_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $event_shift = EventShift::factory()->forEvent($event)->asClosed()->create();

        $subject = EventGuest::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        $this->assertFalse($subject->canUpdateStatus($trooper));
    }

    public function test_can_update_name_returns_false_when_trooper_does_not_own_signup(): void
    {
        $owner_trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $subject = EventGuest::factory()
            ->forEventShift($event_shift)
            ->forTrooper($owner_trooper)
            ->create();

        $this->assertFalse($subject->canUpdateName($other_trooper));
    }
}
