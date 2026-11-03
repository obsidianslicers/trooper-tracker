<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Enums\NotificationFrequency;
use App\Messages\Events\Queries\GetTroopersForDailyEventNotifications;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForDailyEventNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_active_daily_troopers_with_unprocessed_notifications(): void
    {
        $event = Event::factory()->create();

        $eligible = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::DAILY)->create();
        EventNotification::factory()->forEvent($event)->forTrooper($eligible)->asUnprocessed()->create();

        $processed_only = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::DAILY)->create();
        EventNotification::factory()->forEvent($event)->forTrooper($processed_only)->asProcessed()->create();

        $instant = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::INSTANT)->create();
        EventNotification::factory()->forEvent($event)->forTrooper($instant)->asUnprocessed()->create();

        $result = (new GetTroopersForDailyEventNotifications)->handle();

        $this->assertCount(1, $result);
        $this->assertSame($eligible->id, $result->first()->id);
        $this->assertCount(1, $result->first()->event_notifications);
    }
}
