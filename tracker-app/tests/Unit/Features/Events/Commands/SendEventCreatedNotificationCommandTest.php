<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\SendEventCreatedNotificationCommand;
use App\Models\Event;
use App\Models\Trooper;
use Mockery;
use Tests\TestCase;

class SendEventCreatedNotificationCommandTest extends TestCase
{
    public function test_construct_with_event_and_trooper(): void
    {
        // Arrange
        $event = Mockery::mock(Event::class);
        $trooper = Mockery::mock(Trooper::class);

        // Act
        $subject = new SendEventCreatedNotificationCommand($event, $trooper);

        // Assert
        $this->assertSame($event, $subject->event);
        $this->assertSame($trooper, $subject->trooper);
    }
}