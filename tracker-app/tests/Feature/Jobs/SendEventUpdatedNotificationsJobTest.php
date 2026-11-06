<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendEventUpdatedNotificationsJob;
use App\Messages\Events\Commands\SendEventUpdatedNotification;
use App\Messages\Events\Queries\GetTroopersForEventUpdated;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendEventUpdatedNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_sends_command_for_each_trooper_returned_by_query(): void
    {
        $event = Event::factory()->create();
        $changed_fields = [Event::NAME, Event::VENUE];
        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventUpdated::class => collect([$trooper_one, $trooper_two]),
            SendEventUpdatedNotification::class => fn (array $params): mixed => match (true)
            {
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id && $params['changed_fields'] === $changed_fields => null,
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id && $params['changed_fields'] === $changed_fields => null,
            },
        ]);

        $subject = new SendEventUpdatedNotificationsJob($event, $changed_fields);
        $subject->handle();

        $messages->assertCalled(
            GetTroopersForEventUpdated::class,
            fn (array $params): bool => $params['event']->id === $event->id
        );
        $messages->assertCalled(
            SendEventUpdatedNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id && $params['changed_fields'] === $changed_fields
        );
        $messages->assertCalled(
            SendEventUpdatedNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id && $params['changed_fields'] === $changed_fields
        );
    }

    public function test_handle_sends_no_commands_when_query_returns_no_troopers(): void
    {
        $event = Event::factory()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventUpdated::class => collect(),
        ]);

        $subject = new SendEventUpdatedNotificationsJob($event, [Event::NAME]);
        $subject->handle();

        $messages->assertCalled(GetTroopersForEventUpdated::class);

        $this->addToAssertionCount(1);
    }
}
