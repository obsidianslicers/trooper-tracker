<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Events\EventPosted;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Messages\Events\Commands\SendEventCreatedNotification;
use App\Messages\Events\Queries\GetTroopersForEventCreated;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendEventCreatedNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_sends_created_notifications_and_marks_timestamp(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $trooper_one = Trooper::factory()->create();
        $trooper_two = Trooper::factory()->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventCreated::class => collect([$trooper_one, $trooper_two]),
            SendEventCreatedNotification::class => fn (array $params): mixed => match (true)
            {
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id => null,
                $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id => null,
            },
        ]);

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle();

        $messages->assertCalled(GetTroopersForEventCreated::class);
        $messages->assertCalled(
            SendEventCreatedNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_one->id
        );
        $messages->assertCalled(
            SendEventCreatedNotification::class,
            fn (array $params): bool => $params['event']->id === $event->id && $params['trooper']->id === $trooper_two->id
        );

        $this->assertNotNull($event->fresh()->create_notifications_sent_at);
    }

    public function test_handle_rolls_back_marker_when_recipient_query_fails(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();

        $messages = $this->fakeMessages([
            GetTroopersForEventCreated::class => fn (array $params): never => throw new Exception('query failed'),
        ]);

        $subject = new SendEventCreatedNotificationsJob($event);

        $this->expectException(Exception::class);

        try
        {
            $subject->handle();
        }
        finally
        {
            // Rolled back with the failed fan-out, so a retry re-sends to everyone.
            $this->assertNull($event->fresh()->create_notifications_sent_at);
        }
    }

    public function test_handle_skips_trooper_notifications_when_already_marked_sent(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withCreateNotificationsSent()

            ->create();

        $messages = $this->fakeMessages([]);

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle();

        $messages->assertNotCalled(GetTroopersForEventCreated::class);
        $messages->assertNotCalled(SendEventCreatedNotification::class);

        $this->assertNotNull($event->fresh()->create_notifications_sent_at);
    }

    public function test_handle_fires_event_posted_for_plugins(): void
    {
        EventFacade::fake([EventPosted::class]);

        $event = Event::factory()->withCreateNotificationsSent()->create();

        $messages = $this->fakeMessages([]);

        $subject = new SendEventCreatedNotificationsJob($event);
        $subject->handle();

        $messages->assertNotCalled(SendEventCreatedNotification::class);
        EventFacade::assertDispatched(
            EventPosted::class,
            fn (EventPosted $posted): bool => $posted->event->is($event),
        );
    }
}
