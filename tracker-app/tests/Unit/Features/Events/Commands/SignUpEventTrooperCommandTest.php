<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Models\EventShift;
use App\Models\Trooper;
use Mockery;
use Tests\TestCase;

class SignUpEventTrooperCommandTest extends TestCase
{
    public function test_construct_with_parameters(): void
    {
        // Arrange
        $event_shift = Mockery::mock(EventShift::class);
        $trooper = Mockery::mock(Trooper::class);
        $added_by_trooper = Mockery::mock(Trooper::class);

        // Act
        $subject = new SignUpEventTrooperCommand($event_shift, $trooper, $added_by_trooper);

        // Assert
        $this->assertSame($event_shift, $subject->event_shift);
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($added_by_trooper, $subject->added_by_trooper);
    }
}
