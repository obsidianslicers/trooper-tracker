<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetSharedEventRosterQuery;
use App\Models\EventShare;
use Mockery;
use Tests\TestCase;

class GetSharedEventRosterQueryTest extends TestCase
{
    public function test_construct_with_event_share(): void
    {
        // Arrange
        $event_share = Mockery::mock(EventShare::class);

        // Act
        $subject = new GetSharedEventRosterQuery($event_share);

        // Assert
        $this->assertSame($event_share, $subject->event_share);
    }

    public function test_query_is_readonly(): void
    {
        // Arrange
        $event_share = Mockery::mock(EventShare::class);
        $query = new GetSharedEventRosterQuery($event_share);

        // Act & Assert
        $this->expectException(\Error::class);
        $query->event_share = Mockery::mock(EventShare::class);
    }
}
