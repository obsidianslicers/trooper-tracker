<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Models\Event;
use App\Models\Trooper;
use Mockery;
use Tests\TestCase;

class GetEventDisplayQueryTest extends TestCase
{
    public function test_construct_with_event_and_trooper(): void
    {
        // Arrange
        $event = Mockery::mock(Event::class);
        $trooper = Mockery::mock(Trooper::class);

        // Act
        $subject = new GetEventDisplayQuery($event, $trooper);

        // Assert
        $this->assertSame($event, $subject->event);
        $this->assertSame($trooper, $subject->trooper);
    }
}
