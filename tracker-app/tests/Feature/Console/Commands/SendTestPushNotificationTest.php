<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\PushNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendTestPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_trooper_not_found(): void
    {
        $this->artisan('tracker:send-test-push', ['trooper_id' => 99999])
            ->assertExitCode(1);
    }

    public function test_succeeds_and_records_push_notification(): void
    {
        $trooper = Trooper::factory()->create();

        $this->artisan('tracker:send-test-push', ['trooper_id' => $trooper->id])
            ->assertExitCode(0);

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_uses_provided_url_option(): void
    {
        $trooper = Trooper::factory()->create();

        $this->artisan('tracker:send-test-push', [
            'trooper_id' => $trooper->id,
            '--url'      => '/events/details/42',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::URL        => '/events/details/42',
        ]);
    }

    public function test_defaults_url_to_events(): void
    {
        $trooper = Trooper::factory()->create();

        $this->artisan('tracker:send-test-push', ['trooper_id' => $trooper->id])
            ->assertExitCode(0);

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::URL        => '/events',
        ]);
    }

}
