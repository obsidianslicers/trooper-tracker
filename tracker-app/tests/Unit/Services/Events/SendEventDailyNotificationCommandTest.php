<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use App\Models\EventNotification;
use App\Models\Trooper;
use App\Services\Events\SendEventDailyNotificationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendEventDailyNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    private SendEventDailyNotificationCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SendEventDailyNotificationCommand();
    }

    public function test_it_sends_daily_notification_email_to_trooper_with_daily_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $notification = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        Mail::assertQueued(DailyEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }

    public function test_it_does_not_send_email_to_trooper_with_instant_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_it_does_not_send_email_to_trooper_with_never_preference(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::NEVER,
        ]);

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        Mail::assertNothingQueued();
    }

    public function test_it_marks_event_notifications_as_processed_after_sending(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $notification1 = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $notification2 = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        $this->assertNotNull($notification1->fresh()->processed_at);
        $this->assertNotNull($notification2->fresh()->processed_at);
    }

    public function test_it_handles_multiple_event_notifications(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        EventNotification::factory()->count(5)->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        Mail::assertQueued(DailyEventNotification::class, 1);

        $trooper->refresh();
        foreach ($trooper->event_notifications as $notification)
        {
            $this->assertNotNull($notification->processed_at);
        }
    }

    public function test_it_handles_trooper_with_no_event_notifications(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        Mail::assertQueued(DailyEventNotification::class, 1);
    }

    public function test_it_does_not_mark_notifications_as_processed_for_non_daily_troopers(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        $notification = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        $this->assertNull($notification->fresh()->processed_at);
    }

    public function test_it_sets_processed_at_to_current_timestamp(): void
    {
        // Arrange
        Mail::fake();
        Carbon::setTestNow('2026-01-10 12:00:00');

        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $notification = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        $notification->refresh();
        $this->assertNotNull($notification->processed_at);
        $this->assertEqualsWithDelta(
            now()->timestamp,
            $notification->processed_at->timestamp,
            2
        );

        Carbon::setTestNow();
    }

    public function test_it_passes_event_notifications_to_mailable(): void
    {
        // Arrange
        Mail::fake();
        $trooper = Trooper::factory()->create([
            'email' => 'trooper@example.com',
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $notification1 = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $notification2 = EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'processed_at' => null,
        ]);

        $trooper->load('event_notifications');

        // Act
        ($this->subject)($trooper);

        // Assert
        Mail::assertQueued(DailyEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->email);
        });
    }
}
