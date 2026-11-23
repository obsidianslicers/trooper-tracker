<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Commands\PromoteNextInLineEventTrooper;
use App\Messages\Events\Commands\RemoveEventTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class RemoveEventTrooperTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_removes_a_going_trooper_and_promotes_the_next_in_line(): void
    {
        $event_trooper = $this->signUp(EventTrooperStatus::GOING);
        $messages = $this->fakeMessages([PromoteNextInLineEventTrooper::class => null]);

        $subject = new RemoveEventTrooper($event_trooper);
        $subject->handle();

        $this->assertSoftDeleted($event_trooper);
        $messages->assertCalled(
            PromoteNextInLineEventTrooper::class,
            fn (array $params): bool => $params['event_trooper']->is($event_trooper)
        );
    }

    public function test_handle_removes_a_stand_by_trooper_without_promoting_anyone(): void
    {
        $event_trooper = $this->signUp(EventTrooperStatus::STAND_BY);
        $messages = $this->fakeMessages([PromoteNextInLineEventTrooper::class => null]);

        $subject = new RemoveEventTrooper($event_trooper);
        $subject->handle();

        $this->assertSoftDeleted($event_trooper);
        $messages->assertNotCalled(PromoteNextInLineEventTrooper::class);
    }

    private function signUp(EventTrooperStatus $status): EventTrooper
    {
        $event_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();

        return EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([EventTrooper::STATUS => $status]);
    }
}
