<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\SignUpOutcome;
use App\Messages\Events\Commands\CreateEventTrooper;
use App\Messages\Events\Commands\SignUpEventTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SignUpEventTrooperTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    private Trooper $trooper;

    private EventShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $organization = Organization::factory()->create();
        $this->trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($this->trooper)->forOrganization($organization)->asMember()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $this->shift = EventShift::factory()->forEvent($event)->create();
    }

    public function test_handle_signs_up_an_eligible_trooper(): void
    {
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new SignUpEventTrooper($this->shift, $this->trooper, $this->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpOutcome::SIGNED_UP, $result);
        $messages->assertCalled(
            CreateEventTrooper::class,
            fn (array $params): bool => $params['trooper']->is($this->trooper)
                && $params['costume_id'] === null
                && $params['is_attending_without_costume'] === false
        );
    }

    public function test_handle_ignores_a_costume_that_is_not_theirs(): void
    {
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new SignUpEventTrooper(
            $this->shift,
            $this->trooper,
            $this->trooper,
            costume_id: '999999',
        );
        $subject->handle();

        $messages->assertCalled(CreateEventTrooper::class, fn (array $params): bool => $params['costume_id'] === null);
    }

    public function test_handle_attending_without_a_costume_is_not_a_handler(): void
    {
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new SignUpEventTrooper(
            $this->shift,
            $this->trooper,
            $this->trooper,
            is_handler: true,
            costume_id: 'none',
        );
        $subject->handle();

        $messages->assertCalled(
            CreateEventTrooper::class,
            fn (array $params): bool => $params['is_attending_without_costume'] === true
                && $params['is_handler'] === false
        );
    }

    public function test_handle_refuses_a_trooper_already_on_the_shift(): void
    {
        EventTrooper::factory()->forEventShift($this->shift)->forTrooper($this->trooper)->asGoing()->create();
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new SignUpEventTrooper($this->shift, $this->trooper, $this->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpOutcome::REFUSED, $result);
        $messages->assertNotCalled(CreateEventTrooper::class);
    }

    public function test_handle_says_when_the_shift_limit_is_reached(): void
    {
        $this->shift->event->update([Event::SHIFTS_ALLOWED => 1]);
        $other_shift = EventShift::factory()->forEvent($this->shift->event)->create();
        EventTrooper::factory()->forEventShift($other_shift)->forTrooper($this->trooper)->asGoing()->create();

        $subject = new SignUpEventTrooper($this->shift->fresh(), $this->trooper, $this->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpOutcome::SHIFT_LIMIT_REACHED, $result);
    }

    public function test_handle_asks_for_a_station_when_the_shift_has_them(): void
    {
        EventShiftStation::factory()->forEventShift($this->shift)->create();
        $messages = $this->fakeMessages([CreateEventTrooper::class => null]);

        $subject = new SignUpEventTrooper($this->shift, $this->trooper, $this->trooper);
        $result = $subject->handle();

        $this->assertSame(SignUpOutcome::STATION_REQUIRED, $result);
        $messages->assertNotCalled(CreateEventTrooper::class);
    }
}
