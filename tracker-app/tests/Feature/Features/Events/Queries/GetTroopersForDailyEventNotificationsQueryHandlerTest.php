<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Enums\NotificationFrequency;
use App\Features\Events\Queries\GetTroopersForDailyEventNotificationsQuery;
use App\Features\Events\Queries\GetTroopersForDailyEventNotificationsQueryHandler;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForDailyEventNotificationsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_active_daily_troopers_with_unprocessed_notifications(): void
    {
        $event = Event::factory()->create();

        $eligible = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::DAILY)->create();
        EventNotification::factory()->forEvent($event)->forTrooper($eligible)->asUnprocessed()->create();

        $processed_only = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::DAILY)->create();
        EventNotification::factory()->forEvent($event)->forTrooper($processed_only)->asProcessed()->create();

        $instant = Trooper::factory()->asMember()->withNotificationFrequency(NotificationFrequency::INSTANT)->create();
        EventNotification::factory()->forEvent($event)->forTrooper($instant)->asUnprocessed()->create();

        $subject = new GetTroopersForDailyEventNotificationsQueryHandler();

        $result = $subject(new GetTroopersForDailyEventNotificationsQuery());

        $this->assertCount(1, $result);
        $this->assertSame($eligible->id, $result->first()->id);
        $this->assertCount(1, $result->first()->event_notifications);
    }
}
