<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\SignUpChangeOutcome;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Messages\Events\Commands\ChangeEventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChangeEventTrooperStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Queue::fake();
    }

    public function test_handle_cancels_and_tells_the_roster(): void
    {
        $event_trooper = $this->signUp(EventStatus::OPEN, EventTrooperStatus::GOING);

        $subject = new ChangeEventTrooperStatus($event_trooper, EventTrooperStatus::CANCELLED, $event_trooper->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::SAVED, $result);
        $this->assertSame(EventTrooperStatus::CANCELLED, $event_trooper->fresh()->status);
        Queue::assertPushed(SendEventRosterActivityNotificationsJob::class);
    }

    public function test_handle_refuses_a_status_the_event_does_not_offer(): void
    {
        $event_trooper = $this->signUp(EventStatus::OPEN, EventTrooperStatus::GOING);

        $subject = new ChangeEventTrooperStatus($event_trooper, EventTrooperStatus::ATTENDED, $event_trooper->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::STATUS_NOT_OFFERED, $result);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_handle_forbids_a_trooper_choosing_going_on_manual_selection(): void
    {
        $event_trooper = $this->signUp(EventStatus::MANUAL_SELECTION, EventTrooperStatus::STAND_BY);

        $subject = new ChangeEventTrooperStatus($event_trooper, EventTrooperStatus::GOING, $event_trooper->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
        $this->assertSame(EventTrooperStatus::STAND_BY, $event_trooper->fresh()->status);
    }

    public function test_handle_forbids_someone_else(): void
    {
        $event_trooper = $this->signUp(EventStatus::OPEN, EventTrooperStatus::GOING);
        $stranger = Trooper::factory()->asActive()->create();

        $subject = new ChangeEventTrooperStatus($event_trooper, EventTrooperStatus::CANCELLED, $stranger);
        $result = $subject->handle();

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
    }

    private function signUp(EventStatus $event_status, EventTrooperStatus $status): EventTrooper
    {
        $event = Event::factory()->create([Event::STATUS => $event_status]);
        $shift = EventShift::factory()->forEvent($event)->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([EventTrooper::STATUS => $status, EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => true]);
    }
}
