<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Models\EventTrooper;
use Mockery;
use Tests\TestCase;

class UpdateEventTrooperCommandTest extends TestCase
{
    public function test_construct_with_parameters(): void
    {
        // Arrange
        $event_trooper = Mockery::mock(EventTrooper::class);
        $valid_data = ['status' => 'cancelled'];

        // Act
        $subject = new UpdateEventTrooperCommand($event_trooper, $valid_data);

        // Assert
        $this->assertSame($event_trooper, $subject->event_trooper);
        $this->assertEquals($valid_data, $subject->valid_data);
    }
}
