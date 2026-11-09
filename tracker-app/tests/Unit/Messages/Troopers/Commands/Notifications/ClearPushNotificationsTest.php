<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Notifications;

use App\Messages\Troopers\Commands\Notifications\ClearPushNotifications;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearPushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_all_trooper_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->count(5)
            ->forTrooper($trooper)
            ->create();

        $subject = new ClearPushNotifications($trooper);
        $subject->handle();

        $remaining = TrooperNotification::where('notifiable_id', $trooper->id)
            ->where('notifiable_type', Trooper::class)
            ->count();

        $this->assertSame(0, $remaining);
    }

    public function test_handle_does_not_affect_other_troopers_notifications(): void
    {
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->count(2)
            ->forTrooper($trooper1)
            ->create();

        TrooperNotification::factory()
            ->count(3)
            ->forTrooper($trooper2)
            ->create();

        $subject = new ClearPushNotifications($trooper1);
        $subject->handle();

        $trooper1_notifications = TrooperNotification::where('notifiable_id', $trooper1->id)
            ->where('notifiable_type', Trooper::class)
            ->count();

        $trooper2_notifications = TrooperNotification::where('notifiable_id', $trooper2->id)
            ->where('notifiable_type', Trooper::class)
            ->count();

        $this->assertSame(0, $trooper1_notifications);
        $this->assertSame(3, $trooper2_notifications);
    }
}
