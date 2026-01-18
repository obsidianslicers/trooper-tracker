<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForEventCreatedQuery;
use App\Models\Event;
use Mockery;
use Tests\TestCase;

class GetTroopersForEventCreatedQueryTest extends TestCase
{
    public function test_construct_with_event(): void
    {
        // Arrange
        $event = Mockery::mock(Event::class);

        // Act
        $subject = new GetTroopersForEventCreatedQuery($event);

        // Assert
        $this->assertSame($event, $subject->event);
    }
}