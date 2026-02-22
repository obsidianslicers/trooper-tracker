<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Bus\MagicBus;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the SendEventCreatedNotificationsJob class.
 *
 * Validates that the job correctly creates and sends event notifications
 * to eligible troopers when a new event is created, and notifies Discord.
 */
class SendEventCreatedNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private MockInterface $bus_mock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = Event::factory()->create([
            'create_notifications_sent_at' => null,
        ]);

        Http::fake();
        $this->bus_mock = $this->mock(MagicBus::class);
    }

    public function test_handle_sends_notifications_when_not_already_sent(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $troopers = new Collection([$trooper1, $trooper2]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->twice()
            ->andReturn(null);

        $subject = new SendEventCreatedNotificationsJob($this->event);
        $notifier = app(\App\Services\Notifications\DiscordNotifier::class);

        // Act
        $subject->handle($this->bus_mock, $notifier);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
    }

    public function test_handle_does_not_send_when_already_sent(): void
    {
        // Arrange
        $this->event->create_notifications_sent_at = now();
        $this->event->save();

        $this->bus_mock->shouldNotReceive('send');

        $subject = new SendEventCreatedNotificationsJob($this->event);
        $notifier = app(\App\Services\Notifications\DiscordNotifier::class);

        // Act
        $subject->handle($this->bus_mock, $notifier);

        // Assert - timestamp should remain unchanged
        $original_timestamp = $this->event->create_notifications_sent_at;
        $this->event->refresh();
        $this->assertEquals($original_timestamp, $this->event->create_notifications_sent_at);
    }

    public function test_handle_updates_timestamp_after_sending(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $troopers = new Collection([$trooper]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn(null);

        $subject = new SendEventCreatedNotificationsJob($this->event);
        $notifier = app(\App\Services\Notifications\DiscordNotifier::class);

        // Act
        $subject->handle($this->bus_mock, $notifier);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
        $this->assertEqualsWithDelta(now()->timestamp, $this->event->create_notifications_sent_at->timestamp, 2);
    }

    public function test_handle_sends_notifications_with_empty_trooper_collection(): void
    {
        // Arrange
        $troopers = new Collection();

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);
        $notifier = app(\App\Services\Notifications\DiscordNotifier::class);

        // Act
        $subject->handle($this->bus_mock, $notifier);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
    }

    public function test_handle_passes_correct_event_to_query(): void
    {
        // Arrange
        $troopers = new Collection();

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);
        $notifier = app(\App\Services\Notifications\DiscordNotifier::class);

        // Act
        $subject->handle($this->bus_mock, $notifier);
    }

    public function test_handle_passes_troopers_from_query_to_command(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $trooper3 = Trooper::factory()->create();
        $troopers = new Collection([$trooper1, $trooper2, $trooper3]);

        $this->bus_mock->shouldReceive('send')
            ->once()
            ->andReturn($troopers);

        $this->bus_mock->shouldReceive('send')
            ->times(3)
            ->andReturn(null);

        $subject = new SendEventCreatedNotificationsJob($this->event);
        $notifier = app(\App\Services\Notifications\DiscordNotifier::class);

        // Act
        $subject->handle($this->bus_mock, $notifier);
    }

    public function test_job_implements_should_queue(): void
    {
        // Assert
        $subject = new SendEventCreatedNotificationsJob($this->event);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $subject);
    }
}
