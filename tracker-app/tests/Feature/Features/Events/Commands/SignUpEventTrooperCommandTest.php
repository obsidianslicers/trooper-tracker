<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Models\EventShift;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see SignUpEventTrooperCommand
 */
class SignUpEventTrooperCommandTest extends TestCase
{
    public function test_constructor_stores_event_shift_trooper_and_added_by_trooper(): void
    {
        $event_shift = new EventShift([EventShift::ID => 123]);
        $trooper = new Trooper([Trooper::ID => 456]);
        $added_by_trooper = new Trooper([Trooper::ID => 789]);

        $subject = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $added_by_trooper
        );

        $this->assertSame($event_shift, $subject->event_shift);
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($added_by_trooper, $subject->added_by_trooper);
    }
}
