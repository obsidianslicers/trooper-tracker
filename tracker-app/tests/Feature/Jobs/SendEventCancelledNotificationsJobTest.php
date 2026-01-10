<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendEventCancelledNotificationsJob;
use App\Models\Event;
use App\Models\Trooper;
use App\Services\Events\GetTroopersForCancelledEventQuery;
use App\Services\Events\SendEventCancelledNotificationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests for the SendEventCancelledNotificationsJob class.
 *
 * Validates that the job correctly sends cancellation notifications
 * to troopers who signed up for a cancelled event.
 */
class SendEventCancelledNotificationsJobTest extends TestCase
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

        $this->query_mock = $this->mock(GetTroopersForCancelledEventQuery::class);
        $this->command_mock = $this->mock(SendEventCancelledNotificationCommand::class);
    }

    public function test_handle_sends_notifications_when_not_already_sent(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $troopers = new Collection([$trooper1, $trooper2]);

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event)
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $trooper1);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $trooper2);

        $subject = new SendEventCancelledNotificationsJob($this->event);

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

        $subject = new SendEventCancelledNotificationsJob($this->event);

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
        $trooper = Trooper::factory()->create();
        $troopers = new Collection([$trooper]);

        $this->query_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event)
            ->andReturn($troopers);

        $this->command_mock->shouldReceive('__invoke')
            ->once()
            ->with($this->event, $trooper);

        $subject = new SendEventCancelledNotificationsJob($this->event);

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

        $this->command_mock->shouldNotReceive('__invoke');

        $subject = new SendEventCancelledNotificationsJob($this->event);

        // Act
        $subject->handle($this->query_mock, $this->command_mock);

        // Assert
        $this->event->refresh();
        $this->assertNotNull($this->event->create_notifications_sent_at);
    }

    public function test_job_implements_should_queue(): void
    {
        // Assert
        $subject = new SendEventCancelledNotificationsJob($this->event);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $subject);
    }
}
