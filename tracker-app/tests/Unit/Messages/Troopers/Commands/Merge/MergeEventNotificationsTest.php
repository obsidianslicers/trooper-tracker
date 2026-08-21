<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeEventNotifications;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_notifications_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_event = Event::factory()->create();
        $trashed_event = Event::factory()->create();

        $active_notification = EventNotification::factory()
            ->forEvent($active_event)
            ->forTrooper($source_trooper)
            ->create();

        $trashed_notification = EventNotification::factory()
            ->forEvent($trashed_event)
            ->forTrooper($source_trooper)
            ->create();
        $trashed_notification->delete();

        MergeEventNotifications::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_notifications', [
            EventNotification::ID => $active_notification->id,
            EventNotification::EVENT_ID => $active_event->id,
            EventNotification::TROOPER_ID => $target_trooper->id,
            EventNotification::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_event_notifications', [
            EventNotification::ID => $trashed_notification->id,
            EventNotification::EVENT_ID => $trashed_event->id,
            EventNotification::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_notifications', [
            EventNotification::ID => $active_notification->id,
            EventNotification::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_notifications', [
            EventNotification::ID => $trashed_notification->id,
            EventNotification::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_restores_target_notification_and_merges_processed_and_sent_dates(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event = Event::factory()->create();

        $target_notification = EventNotification::factory()
            ->forEvent($event)
            ->forTrooper($target_trooper)
            ->create([
                EventNotification::PROCESSED_AT => now()->subDays(2),
                EventNotification::SENT_AT => now()->subDay(),
            ]);
        $target_notification->delete();

        $source_notification = EventNotification::factory()
            ->forEvent($event)
            ->forTrooper($source_trooper)
            ->create([
                EventNotification::PROCESSED_AT => now()->subDay(),
                EventNotification::SENT_AT => now(),
            ]);

        MergeEventNotifications::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $target_notification->refresh();

        $this->assertDatabaseHas('tt_event_notifications', [
            EventNotification::ID => $target_notification->id,
            EventNotification::EVENT_ID => $event->id,
            EventNotification::TROOPER_ID => $target_trooper->id,
            EventNotification::DELETED_AT => null,
        ]);

        $this->assertDatabaseMissing('tt_event_notifications', [
            EventNotification::ID => $source_notification->id,
        ]);

        $this->assertTrue(
            $target_notification->processed_at->equalTo($source_notification->processed_at),
        );
        $this->assertTrue(
            $target_notification->sent_at->equalTo($source_notification->sent_at),
        );
        $this->assertSame(
            1,
            EventNotification::query()
                ->where(EventNotification::EVENT_ID, $event->id)
                ->where(EventNotification::TROOPER_ID, $target_trooper->id)
                ->count(),
        );
    }
}
