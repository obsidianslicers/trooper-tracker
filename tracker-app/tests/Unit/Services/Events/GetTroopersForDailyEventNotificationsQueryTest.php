<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\NotificationFrequency;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Services\Events\GetTroopersForDailyEventNotificationsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetTroopersForDailyEventNotificationsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_troopers_with_daily_frequency_and_pending_notifications(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($trooper));
    }

    public function test_it_excludes_troopers_with_instant_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_it_excludes_troopers_with_never_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::NEVER,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_it_excludes_troopers_without_pending_notifications(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        // Create notification that's already been processed
        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => Carbon::yesterday(),
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_it_excludes_inactive_troopers(): void
    {
        // Arrange
        $retired_trooper = Trooper::factory()->asRetired()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $retired_trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_it_excludes_pending_troopers(): void
    {
        // Arrange
        $pending_trooper = Trooper::factory()->asPending()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $pending_trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_it_returns_multiple_troopers_with_pending_notifications(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $trooper2 = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $trooper3 = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper1->id,
            'processed_at' => null,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper2->id,
            'processed_at' => null,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper3->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains($trooper1));
        $this->assertTrue($result->contains($trooper2));
        $this->assertTrue($result->contains($trooper3));
    }

    public function test_it_returns_empty_collection_when_no_troopers_need_notifications(): void
    {
        // Arrange - create troopers with processed notifications
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => Carbon::now(),
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(0, $result);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_it_eager_loads_event_notifications_relationship(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertTrue($result->first()->relationLoaded('event_notifications'));
    }

    public function test_it_eager_loads_only_unprocessed_event_notifications(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        // Create both processed and unprocessed notifications
        $unprocessed = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $processed = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => Carbon::yesterday(),
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $trooper_result = $result->first();
        $this->assertCount(1, $trooper_result->event_notifications);
        $this->assertEquals($unprocessed->id, $trooper_result->event_notifications->first()->id);
    }

    public function test_it_handles_troopers_with_multiple_pending_notifications(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->count(5)->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $subject = new GetTroopersForDailyEventNotificationsQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertCount(5, $result->first()->event_notifications);
    }
}
