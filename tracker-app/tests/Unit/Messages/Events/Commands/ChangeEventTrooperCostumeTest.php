<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\SignUpChangeOutcome;
use App\Messages\Events\Commands\ChangeEventTrooperCostume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\EventRosterCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeEventTrooperCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_attending_without_a_costume_confirms_a_pending_spot(): void
    {
        $event_trooper = $this->pending();

        $subject = new ChangeEventTrooperCostume($event_trooper, 'none', $event_trooper->trooper);
        $result = $subject->handle(new EventRosterCapacityService);

        $event_trooper->refresh();
        $this->assertSame(SignUpChangeOutcome::SAVED, $result);
        $this->assertTrue($event_trooper->is_attending_without_costume);
        $this->assertNull($event_trooper->costume_id);
        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->status);
    }

    public function test_handle_forbids_someone_else(): void
    {
        $event_trooper = $this->pending();
        $stranger = Trooper::factory()->asActive()->create();

        $subject = new ChangeEventTrooperCostume($event_trooper, 'none', $stranger);
        $result = $subject->handle(new EventRosterCapacityService);

        $this->assertSame(SignUpChangeOutcome::FORBIDDEN, $result);
        $this->assertFalse($event_trooper->fresh()->is_attending_without_costume);
    }

    private function pending(): EventTrooper
    {
        $event = Event::factory()->create([Event::TROOPERS_ALLOWED => null]);
        $shift = EventShift::factory()->forEvent($event)->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([EventTrooper::STATUS => EventTrooperStatus::PENDING]);
    }
}
