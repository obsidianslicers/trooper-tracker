<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\NotificationFrequency;
use App\Features\Events\Queries\GetTroopersForDailyEventNotificationsQuery;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class SendDailyEventNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $bus_mock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bus_mock = $this->mock(MagicBus::class);
    }

    public function test_it_sends_daily_notifications_to_eligible_troopers(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $event = Event::factory()->create();

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'event_id' => $event->id,
            'processed_at' => null,
        ]);

        $troopers = new Collection([$trooper]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($query)
            {
                return $query instanceof GetTroopersForDailyEventNotificationsQuery;
            }))
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($command) use ($trooper)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand
                    && $command->trooper->id === $trooper->id;
            }))
            ->andReturn(null);

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }

    public function test_it_sends_notifications_to_multiple_troopers(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $trooper2 = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $trooper3 = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $troopers = new Collection([$trooper1, $trooper2, $trooper3]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($query)
            {
                return $query instanceof GetTroopersForDailyEventNotificationsQuery;
            }))
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->times(3)
            ->with(\Mockery::on(function ($command)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand;
            }))
            ->andReturn(null);

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }

    public function test_it_handles_empty_trooper_collection_gracefully(): void
    {
        // Arrange
        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($query)
            {
                return $query instanceof GetTroopersForDailyEventNotificationsQuery;
            }))
            ->andReturn(new Collection());

        // Should not send any commands
        $this->bus_mock->shouldNotReceive('send')
            ->with(\Mockery::on(function ($command)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand;
            }));

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }

    public function test_it_only_processes_troopers_with_unprocessed_notifications(): void
    {
        // Arrange
        $trooper_with_pending = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $trooper_without_pending = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $event = Event::factory()->create();

        // Trooper with unprocessed notification
        EventNotification::factory()->create([
            'trooper_id' => $trooper_with_pending->id,
            'event_id' => $event->id,
            'processed_at' => null,
        ]);

        // Trooper with already processed notification
        EventNotification::factory()->create([
            'trooper_id' => $trooper_without_pending->id,
            'event_id' => $event->id,
            'processed_at' => now(),
        ]);

        // Query should only return trooper with pending notifications
        $troopers = new Collection([$trooper_with_pending]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($query)
            {
                return $query instanceof GetTroopersForDailyEventNotificationsQuery;
            }))
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($command) use ($trooper_with_pending)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand
                    && $command->trooper->id === $trooper_with_pending->id;
            }))
            ->andReturn(null);

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }

    public function test_it_uses_get_troopers_for_daily_event_notifications_query(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $event = Event::factory()->create();

        EventNotification::factory()->create([
            'trooper_id' => $trooper->id,
            'event_id' => $event->id,
            'processed_at' => null,
        ]);

        // Get a fresh instance without the mock
        $this->app->forgetInstance(MagicBus::class);
        $bus = app(MagicBus::class);

        $troopers_before = $bus->send(new GetTroopersForDailyEventNotificationsQuery());

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);

        // Assert - verify query would no longer return this trooper after processing
        $troopers_after = $bus->send(new GetTroopersForDailyEventNotificationsQuery());
        $this->assertCount(1, $troopers_before);
        $this->assertCount(0, $troopers_after);
    }

    public function test_it_processes_each_trooper_individually(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $trooper2 = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $troopers = new Collection([$trooper1, $trooper2]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        // Verify each trooper gets their own command
        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($command) use ($trooper1)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand
                    && $command->trooper->id === $trooper1->id;
            }))
            ->andReturn(null);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->with(\Mockery::on(function ($command) use ($trooper2)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand
                    && $command->trooper->id === $trooper2->id;
            }))
            ->andReturn(null);

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }

    public function test_it_completes_successfully_when_no_troopers_need_notifications(): void
    {
        // Arrange - create troopers with INSTANT or NEVER frequency
        Trooper::factory()->count(3)->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        Trooper::factory()->count(2)->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER,
        ]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(new Collection());

        // Act & Assert - should complete without errors
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }

    public function test_it_handles_large_number_of_troopers(): void
    {
        // Arrange
        $trooper_count = 25;
        $troopers = Trooper::factory()->count($trooper_count)->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->times($trooper_count)
            ->with(\Mockery::on(function ($command)
            {
                return $command instanceof \App\Features\Events\Commands\SendEventDailyNotificationCommand;
            }))
            ->andReturn(null);

        // Act
        $this->artisan('tracker:send-daily-event-notifications')->assertExitCode(0);
    }
}
