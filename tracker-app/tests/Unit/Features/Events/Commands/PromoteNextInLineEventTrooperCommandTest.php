<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Models\EventTrooper;
use Mockery;
use Tests\TestCase;

class PromoteNextInLineEventTrooperCommandTest extends TestCase
{
    public function test_construct_with_event_trooper(): void
    {
        // Arrange
        $event_trooper = Mockery::mock(EventTrooper::class);

        // Act
        $subject = new PromoteNextInLineEventTrooperCommand($event_trooper);

        // Assert
        $this->assertSame($event_trooper, $subject->event_trooper);
    }
}
