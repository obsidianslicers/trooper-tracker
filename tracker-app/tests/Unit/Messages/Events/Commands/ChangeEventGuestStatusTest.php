<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\SignUpChangeOutcome;
use App\Messages\Events\Commands\ChangeEventGuestStatus;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeEventGuestStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_lets_the_trooper_who_added_the_guest_cancel(): void
    {
        [$guest, $trooper] = $this->guest();

        $subject = new ChangeEventGuestStatus($guest, EventGuestStatus::CANCELLED, $trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::SAVED, $result);
        $this->assertSame(EventGuestStatus::CANCELLED, $guest->fresh()->status);
    }

    public function test_handle_forbids_someone_else(): void
    {
        [$guest] = $this->guest();
        $stranger = Trooper::factory()->asActive()->create();

        $subject = new ChangeEventGuestStatus($guest, EventGuestStatus::CANCELLED, $stranger);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
        $this->assertSame(EventGuestStatus::GOING, $guest->fresh()->status);
    }

    public function test_handle_forbids_a_trooper_choosing_going_on_manual_selection(): void
    {
        [$guest, $trooper] = $this->guest(EventStatus::MANUAL_SELECTION);

        $subject = new ChangeEventGuestStatus($guest, EventGuestStatus::GOING, $trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
    }

    /**
     * @return array{0: EventGuest, 1: Trooper}
     */
    private function guest(EventStatus $status = EventStatus::OPEN): array
    {
        $event = Event::factory()->create([Event::STATUS => $status]);
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $guest = EventGuest::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();

        return [$guest, $trooper];
    }
}
