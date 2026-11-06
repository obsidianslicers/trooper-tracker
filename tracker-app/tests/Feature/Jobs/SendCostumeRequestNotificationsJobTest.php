<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendCostumeRequestNotificationsJob;
use App\Messages\Troopers\Commands\Costumes\SendCostumeRequestNotifications;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendCostumeRequestNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_calls_the_message(): void
    {
        $trooper = Trooper::factory()->create();
        $messages = $this->fakeMessages([SendCostumeRequestNotifications::class => null]);

        $subject = new SendCostumeRequestNotificationsJob($trooper, [7, 8]);
        $subject->handle();

        $messages->assertCalled(
            SendCostumeRequestNotifications::class,
            fn (array $params): bool => $params['trooper']->is($trooper)
                && $params['trooper_costume_ids'] === [7, 8]
        );
    }
}
