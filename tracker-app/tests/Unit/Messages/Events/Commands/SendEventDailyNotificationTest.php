<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use App\Messages\Events\Commands\SendEventDailyNotification;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendEventDailyNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_queues_daily_digest_email_for_daily_preference(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);
        $event = Event::factory()->create();
        EventNotification::factory()->create([
            EventNotification::TROOPER_ID => $trooper->id,
            EventNotification::EVENT_ID => $event->id,
            EventNotification::PROCESSED_AT => null,
        ]);

        $command = new SendEventDailyNotification(trooper: $trooper);

        $command->handle();

        Mail::assertQueued(DailyEventNotification::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->{Trooper::EMAIL});
        });
    }

    public function test_handle_marks_all_notifications_as_processed(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();

        $notification1 = EventNotification::factory()->create([
            EventNotification::TROOPER_ID => $trooper->id,
            EventNotification::EVENT_ID => $event1->id,
            EventNotification::PROCESSED_AT => null,
        ]);
        $notification2 = EventNotification::factory()->create([
            EventNotification::TROOPER_ID => $trooper->id,
            EventNotification::EVENT_ID => $event2->id,
            EventNotification::PROCESSED_AT => null,
        ]);

        $command = new SendEventDailyNotification(trooper: $trooper);

        $command->handle();

        $notification1->refresh();
        $notification2->refresh();

        $this->assertNotNull($notification1->{EventNotification::PROCESSED_AT});
        $this->assertNotNull($notification2->{EventNotification::PROCESSED_AT});
    }

    public function test_handle_does_not_send_email_for_instant_preference(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $command = new SendEventDailyNotification(trooper: $trooper);

        $command->handle();

        Mail::assertNothingQueued();
    }

    public function test_handle_returns_null_when_email_invalid(): void
    {
        Mail::fake();

        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => '',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $command = new SendEventDailyNotification(trooper: $trooper);

        $result = $command->handle();

        $this->assertNull($result);
        Mail::assertNothingQueued();
    }
}
