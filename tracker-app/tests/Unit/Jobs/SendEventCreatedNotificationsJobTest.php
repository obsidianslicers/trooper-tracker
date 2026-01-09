<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\Trooper;
use App\Services\Events\GetTroopersForEventCreatedNotificationQuery;
use App\Services\Events\SendEventCreatedNotificationsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the SendEventCreatedNotificationsJob class.
 *
 * Validates that the job correctly creates and sends event notifications
 * to eligible troopers when a new event is created.
 */
class SendEventCreatedNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private MockInterface $query_mock;
    private MockInterface $command_mock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->event = Event::factory()->create([
            'create_notifications_sent_at' => null,
        ]);

        $this->query_mock = $this->mock(GetTroopersForEventCreatedNotificationQuery::class);
        $this->command_mock = $this->mock(SendEventCreatedNotificationsCommand::class);
    }

    public function test_handle_sends_notifications_when_not_already_sent(): void
    {
        // Arrange
        $troopers = new Collection([
            Trooper::factory()->create(),
            Trooper::factory()->create(),
        ]);

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event)
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
    }

    public function test_handle_does_not_send_when_already_sent(): void
    {
        // Arrange
        $this->event->create_notifications_sent_at = now();
        $this->event->save();

        $this->query_mock->shouldNotReceive('__invoke');

        $this->command_mock->shouldNotReceive('__invoke');

        $subject = new SendEventCreatedNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);

        // Assert - timestamp should remain unchanged
        $original_timestamp = $this->event->create_notifications_sent_at;
        $this->event->refresh();
        $this->assertEquals($original_timestamp, $this->event->create_notifications_sent_at);
    }

    public function test_handle_updates_timestamp_after_sending(): void
    {
        // Arrange
        $troopers = new Collection([Trooper::factory()->create()]);

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event)
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
        $this->assertEqualsWithDelta(now()->timestamp, $this->event->create_notifications_sent_at->timestamp, 2);
    }

    public function test_handle_sends_notifications_with_empty_trooper_collection(): void
    {
        // Arrange
        $troopers = new Collection();

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event)
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
    }

    public function test_handle_passes_correct_event_to_query(): void
    {
        // Arrange
        $troopers = new Collection();

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event)
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);
    }

    public function test_handle_passes_troopers_from_query_to_command(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $trooper3 = Trooper::factory()->create();
        $troopers = new Collection([$trooper1, $trooper2, $trooper3]);

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $troopers);

        $subject = new SendEventCreatedNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);
    }

    public function test_job_implements_should_queue(): void
    {
        // Assert
        $subject = new SendEventCreatedNotificationsJob($this->event);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $subject);
    }
}
