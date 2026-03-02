<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\ShareEventRosterCommand;
use App\Models\Event;
use App\Models\Trooper;
use Mockery;
use Tests\TestCase;

class ShareEventRosterCommandTest extends TestCase
{
    public function test_construct_with_parameters(): void
    {
        // Arrange
        $event = Mockery::mock(Event::class);
        $recipient_email = 'coordinator@example.com';
        $shared_by_trooper = Mockery::mock(Trooper::class);

        // Act
        $subject = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);

        // Assert
        $this->assertSame($event, $subject->event);
        $this->assertSame($recipient_email, $subject->recipient_email);
        $this->assertSame($shared_by_trooper, $subject->shared_by_trooper);
    }
}
