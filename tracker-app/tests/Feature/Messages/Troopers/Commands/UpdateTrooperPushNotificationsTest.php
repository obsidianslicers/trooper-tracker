<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\UpdateTrooperPushNotifications;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrooperPushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_trooper_push_notifications_setting(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->create([
                Trooper::PUSH_NOTIFICATIONS_ENABLED => false,
            ]);

        $subject = new UpdateTrooperPushNotifications(
            trooper: $trooper,
            push_notifications_enabled: true,
        );

        $subject->handle();

        $trooper->refresh();

        $this->assertTrue($trooper->{Trooper::PUSH_NOTIFICATIONS_ENABLED});
        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->{Trooper::ID},
            Trooper::PUSH_NOTIFICATIONS_ENABLED => true,
        ]);
    }
}
