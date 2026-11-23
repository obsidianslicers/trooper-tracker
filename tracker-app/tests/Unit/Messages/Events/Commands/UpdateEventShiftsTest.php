<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Jobs\ReconcileEventRosterJob;
use App\Messages\Events\Commands\UpdateEventShifts;
use App\Messages\Events\Commands\UpdateEventShiftStations;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class UpdateEventShiftsTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->admin = Trooper::factory()->asSystemOwner()->create();
    }

    public function test_handle_updates_a_shift_and_fits_the_event_around_it(): void
    {
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $shift = EventShift::factory()->forEvent($event)->create();
        $this->fakeMessages([UpdateEventShiftStations::class => null]);

        $subject = new UpdateEventShifts($event, [
            $shift->id => ['date' => '2030-05-04', 'starts_at' => '10:00', 'ends_at' => '14:00'],
        ], $this->admin);
        $subject->handle();

        $shift->refresh();
        $event->refresh();
        $this->assertSame('2030-05-04 10:00', $shift->shift_starts_at->format('Y-m-d H:i'));
        $this->assertSame('2030-05-04 14:00', $shift->shift_ends_at->format('Y-m-d H:i'));
        $this->assertSame('2030-05-04 10:00', $event->event_start->format('Y-m-d H:i'));
        $this->assertSame('2030-05-04 14:00', $event->event_end->format('Y-m-d H:i'));
        Queue::assertNotPushed(ReconcileEventRosterJob::class);
    }

    public function test_handle_adds_a_new_shift_open_on_an_open_event(): void
    {
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $this->fakeMessages([UpdateEventShiftStations::class => null]);

        $subject = new UpdateEventShifts($event, [
            0 => ['date' => '2030-05-04', 'starts_at' => '10:00', 'ends_at' => '14:00'],
        ], $this->admin);
        $subject->handle();

        $shift = $event->event_shifts()->sole();
        $this->assertSame(EventStatus::OPEN, $shift->status);
    }

    public function test_handle_saves_stations_and_reconciles_the_roster_when_given_stations(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $stations = [['name' => 'Gate', 'capacity' => 2]];
        $messages = $this->fakeMessages([UpdateEventShiftStations::class => null]);

        $subject = new UpdateEventShifts($event, [
            $shift->id => [
                'date' => '2030-05-04',
                'starts_at' => '10:00',
                'ends_at' => '14:00',
                'stations' => $stations,
            ],
        ], $this->admin);
        $subject->handle();

        $messages->assertCalled(
            UpdateEventShiftStations::class,
            fn (array $params): bool => $params['stations'] === $stations
                && $params['event_shift']->id === $shift->id
        );
        Queue::assertPushed(ReconcileEventRosterJob::class);
    }
}
