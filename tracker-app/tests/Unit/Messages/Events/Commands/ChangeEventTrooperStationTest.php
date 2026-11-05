<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Commands\ChangeEventTrooperStation;
use App\Messages\Events\Commands\PromoteNextInLineEventTrooper;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\EventRosterCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ChangeEventTrooperStationTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    private EventShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $event = Event::factory()->create([Event::TROOPERS_ALLOWED => null]);
        $this->shift = EventShift::factory()->forEvent($event)->create();
    }

    public function test_handle_moves_trooper_and_promotes_next_in_line_at_full_old_station(): void
    {
        $old_station = $this->station(1);
        $new_station = $this->station(2);
        $event_trooper = $this->goingAt($old_station);
        $messages = $this->fakeMessages([PromoteNextInLineEventTrooper::class => null]);

        $this->change($event_trooper, $new_station->id);

        $event_trooper->refresh();
        $this->assertSame($new_station->id, $event_trooper->event_shift_station_id);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->status);
        $messages->assertCalled(
            PromoteNextInLineEventTrooper::class,
            fn (array $params): bool => $params['event_shift_station_id'] === $old_station->id
        );
    }

    public function test_handle_puts_trooper_on_standby_when_new_station_is_full(): void
    {
        $old_station = $this->station(2);
        $new_station = $this->station(1);
        $this->goingAt($new_station);
        $event_trooper = $this->goingAt($old_station);
        $messages = $this->fakeMessages([PromoteNextInLineEventTrooper::class => null]);

        $this->change($event_trooper, $new_station->id);

        $this->assertSame(EventTrooperStatus::STAND_BY, $event_trooper->fresh()->status);
        $messages->assertNotCalled(PromoteNextInLineEventTrooper::class);
    }

    private function change(EventTrooper $event_trooper, ?int $station_id): void
    {
        (new ChangeEventTrooperStation($event_trooper, $station_id))
            ->handle(new EventRosterCapacityService);
    }

    private function station(int $allowed): EventShiftStation
    {
        return EventShiftStation::factory()->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => $allowed])->create();
    }

    private function goingAt(EventShiftStation $station): EventTrooper
    {
        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forEventShiftStation($station)
            ->forTrooper(Trooper::factory()->create())
            ->withCostume(Costume::factory()->create())
            ->asGoing()
            ->create();
    }
}
