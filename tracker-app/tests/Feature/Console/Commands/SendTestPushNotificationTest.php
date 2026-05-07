<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Trooper;
use App\Notifications\Tests\TestPushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTestPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_trooper_not_found(): void
    {
        $this->artisan('tracker:send-test-push', ['trooper_id' => 99999])
            ->assertExitCode(1);
    }

    public function test_succeeds_and_sends_notification(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->create();

        $this->artisan('tracker:send-test-push', ['trooper_id' => $trooper->id])
            ->assertExitCode(0);

        Notification::assertSentTo($trooper, TestPushNotification::class);
    }

    public function test_uses_provided_url_option(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->create();

        $this->artisan('tracker:send-test-push', [
            'trooper_id' => $trooper->id,
            '--url'      => '/events/details/42',
        ])->assertExitCode(0);

        Notification::assertSentTo($trooper, TestPushNotification::class);
    }

    public function test_defaults_url_to_events(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->create();

        $this->artisan('tracker:send-test-push', ['trooper_id' => $trooper->id])
            ->assertExitCode(0);

        Notification::assertSentTo($trooper, TestPushNotification::class);
    }
}
