<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendTroopVerificationNotificationsJob;
use App\Messages\Events\Commands\Verification\SendTroopVerificationNotifications;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendTroopVerificationNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_calls_the_message(): void
    {
        $event_trooper = EventTrooper::factory()->create();
        $messages = $this->fakeMessages([SendTroopVerificationNotifications::class => null]);

        $subject = new SendTroopVerificationNotificationsJob($event_trooper);
        $subject->handle();

        $messages->assertCalled(
            SendTroopVerificationNotifications::class,
            fn (array $params): bool => $params['event_trooper']->is($event_trooper)
        );
    }
}
