<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\AddEventTrooperOutcome;
use App\Messages\Events\Commands\AddEventTrooper;
use App\Messages\Events\Commands\CreateEventTrooper;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class AddEventTrooperTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    private EventShift $event_shift;

    private Trooper $admin;

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();
        $this->admin = Trooper::factory()->asSystemOwner()->create();
        $this->trooper = Trooper::factory()->asActive()->create();
    }

    public function test_handle_signs_the_trooper_up(): void
    {
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new AddEventTrooper($this->event_shift, $this->trooper, $this->admin);
        $outcome = $subject->handle();

        $this->assertSame(AddEventTrooperOutcome::ADDED, $outcome);
        $messages->assertCalled(
            CreateEventTrooper::class,
            fn (array $params): bool => $params['trooper']->is($this->trooper)
                && $params['added_by_trooper']->is($this->admin)
        );
    }

    public function test_handle_drops_a_costume_and_club_the_trooper_cannot_use(): void
    {
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);
        $not_theirs = Costume::factory()->create();
        $not_eligible = Organization::factory()->create();

        $subject = new AddEventTrooper(
            $this->event_shift,
            $this->trooper,
            $this->admin,
            costume_id: $not_theirs->id,
            organization_id: $not_eligible->id,
        );
        $subject->handle();

        $messages->assertCalled(
            CreateEventTrooper::class,
            fn (array $params): bool => $params['costume_id'] === null && $params['organization_id'] === null
        );
    }

    public function test_handle_refuses_a_trooper_already_on_the_shift(): void
    {
        EventTrooper::factory()->forEventShift($this->event_shift)->forTrooper($this->trooper)->asGoing()->create();
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new AddEventTrooper($this->event_shift, $this->trooper, $this->admin);
        $outcome = $subject->handle();

        $this->assertSame(AddEventTrooperOutcome::ALREADY_SIGNED_UP, $outcome);
        $messages->assertNotCalled(CreateEventTrooper::class);
    }

    public function test_handle_requires_a_station_when_the_shift_has_stations(): void
    {
        EventShiftStation::factory()->create([EventShiftStation::EVENT_SHIFT_ID => $this->event_shift->id]);
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new AddEventTrooper($this->event_shift, $this->trooper, $this->admin);
        $outcome = $subject->handle();

        $this->assertSame(AddEventTrooperOutcome::STATION_REQUIRED, $outcome);
        $messages->assertNotCalled(CreateEventTrooper::class);
    }
}
