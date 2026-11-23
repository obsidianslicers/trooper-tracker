<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\SignUpChangeOutcome;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Messages\Events\Commands\ReinstateEventTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReinstateEventTrooperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_handle_puts_a_cancelled_trooper_back_on_the_shift(): void
    {
        $event_trooper = $this->cancelled(troopers_allowed: null);

        $subject = new ReinstateEventTrooper($event_trooper, $event_trooper->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::SAVED, $result);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
        Queue::assertPushed(SendEventRosterActivityNotificationsJob::class);
    }

    public function test_handle_puts_them_on_stand_by_when_the_shift_is_full(): void
    {
        $event_trooper = $this->cancelled(troopers_allowed: 1);
        EventTrooper::factory()
            ->forEventShift($event_trooper->event_shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::GOING,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => true,
            ]);

        $subject = new ReinstateEventTrooper($event_trooper, $event_trooper->trooper);
        $subject->handle();

        $this->assertSame(EventTrooperStatus::STAND_BY, $event_trooper->fresh()->status);
    }

    public function test_handle_forbids_someone_else(): void
    {
        $event_trooper = $this->cancelled(troopers_allowed: null);
        $stranger = Trooper::factory()->asActive()->create();

        $subject = new ReinstateEventTrooper($event_trooper, $stranger);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper->fresh()->status);
    }

    private function cancelled(?int $troopers_allowed): EventTrooper
    {
        $event = Event::factory()->create([Event::TROOPERS_ALLOWED => $troopers_allowed]);
        $shift = EventShift::factory()->forEvent($event)->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => true,
            ]);
    }
}
