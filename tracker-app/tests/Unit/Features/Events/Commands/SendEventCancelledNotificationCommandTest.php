<?php

declare(strict_types=1);
namespace

Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCancelledNotificationCommand;
use App\Models\Event;
use App\Models\Trooper;
use Mockery;
use Tests\TestCase;

class SendEventCancelledNotificationCommandTest extends TestCase
{
    public function test_construct_with_event_and_trooper(): void
    {
        // Arrange
        $event = Mockery::mock(Event::class);
        $trooper = Mockery::mock(Trooper::class);

        // Act
        $subject = new SendEventCancelledNotificationCommand($event, $trooper);

        // Assert
        $this->assertSame($event, $subject->event);
        $this->assertSame($trooper, $subject->trooper);
    }
}