<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Models\EventShift;
use App\Models\Trooper;
use Mockery;
use Tests\TestCase;

class GetEventShiftDisplayQueryTest extends TestCase
{
    public function test_construct_with_event_shift(): void
    {
        // Arrange
        $event_shift = Mockery::mock(EventShift::class);
        $trooper = Mockery::mock(Trooper::class);

        // Act
        $subject = new GetEventShiftDisplayQuery($event_shift, $trooper);

        // Assert
        $this->assertSame($event_shift, $subject->event_shift);
        $this->assertSame($trooper, $subject->trooper);
    }
}
