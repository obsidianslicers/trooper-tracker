<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Messages\Events\Commands\SignUpEventGuests;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpEventGuestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_adds_each_named_guest_once(): void
    {
        [$shift, $trooper] = $this->goingTrooper();

        $subject = new SignUpEventGuests($shift, $trooper, [' Han ', '', 'Leia', 'Han']);
        $result = $subject->handle();

        $this->assertTrue($result);
        $this->assertEqualsCanonicalizing(['Han', 'Leia'], $shift->event_guests()->pluck(EventGuest::NAME)->all());
        $this->assertSame(EventGuestStatus::GOING, $shift->event_guests()->first()->status);
        $this->assertSame($trooper->id, $shift->event_guests()->first()->added_by_trooper_id);
    }

    public function test_handle_adds_guests_on_stand_by_for_manual_selection(): void
    {
        [$shift, $trooper] = $this->goingTrooper();
        $shift->event->update([Event::STATUS => EventStatus::MANUAL_SELECTION]);

        $subject = new SignUpEventGuests($shift->fresh(), $trooper, ['Han']);
        $subject->handle();

        $this->assertSame(EventGuestStatus::STAND_BY, $shift->event_guests()->first()->status);
    }

    public function test_handle_refuses_a_trooper_not_going(): void
    {
        $shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $trooper = Trooper::factory()->asActive()->create();

        $subject = new SignUpEventGuests($shift, $trooper, ['Han']);
        $result = $subject->handle();

        $this->assertFalse($result);
        $this->assertSame(0, $shift->event_guests()->count());
    }

    /**
     * @return array{0: EventShift, 1: Trooper}
     */
    private function goingTrooper(): array
    {
        $shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $trooper = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();

        return [$shift, $trooper];
    }
}
