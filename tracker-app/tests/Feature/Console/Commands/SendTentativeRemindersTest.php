<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Messages\Events\Commands\SendTentativeReminder;
use App\Messages\Events\Queries\GetTentativeEventTroopers;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendTentativeRemindersTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_command_dispatches_query_and_sends_command_for_each_event_trooper(): void
    {
        $event = Event::factory()->withEventStart(now()->addDays(3))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper1 = Trooper::factory()->asMember()->create();
        $trooper2 = Trooper::factory()->asMember()->create();
        $et1 = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper1)->asTentative()->create();
        $et2 = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper2)->asTentative()->create();

        $event_troopers = collect([$et1, $et2]);

        $messages = $this->fakeMessages([
            GetTentativeEventTroopers::class => $event_troopers,
            SendTentativeReminder::class => null,
        ]);

        $this->artisan('tracker:send-tentative-reminders')->assertExitCode(0);

        $messages->assertCalled(GetTentativeEventTroopers::class);
        $messages->assertCalled(
            SendTentativeReminder::class,
            fn (array $params): bool => $params['event_trooper']->id === $et1->id
        );
        $messages->assertCalled(
            SendTentativeReminder::class,
            fn (array $params): bool => $params['event_trooper']->id === $et2->id
        );
    }

    public function test_command_handles_no_tentative_troopers(): void
    {
        $messages = $this->fakeMessages([
            GetTentativeEventTroopers::class => collect([]),
        ]);

        $this->artisan('tracker:send-tentative-reminders')->assertExitCode(0);

        $messages->assertCalled(GetTentativeEventTroopers::class);
    }
}
