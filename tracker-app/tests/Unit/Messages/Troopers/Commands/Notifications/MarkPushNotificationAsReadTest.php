<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Notifications;

use App\Messages\Troopers\Commands\Notifications\MarkPushNotificationAsRead;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkPushNotificationAsReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_marks_notification_as_read(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->asUnread()
            ->create();

        $subject = new MarkPushNotificationAsRead($notification);
        $subject->handle();

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_handle_does_not_affect_other_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $notification1 = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->asUnread()
            ->create();

        $notification2 = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->asUnread()
            ->create();

        $subject = new MarkPushNotificationAsRead($notification1);
        $subject->handle();

        $notification1->refresh();
        $notification2->refresh();

        $this->assertNotNull($notification1->read_at);
        $this->assertNull($notification2->read_at);
    }
}
