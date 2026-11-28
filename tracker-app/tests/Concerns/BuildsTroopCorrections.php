<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Messages\Events\Commands\Corrections\RequestTroopCorrection;
use Carbon\Carbon;

/**
 * A completed event two months back (past every grace period), and a request for credit for it.
 */
trait BuildsTroopCorrections
{
    protected function completedShift(?Trooper $coordinator = null, string $name = 'Parade of Lights'): EventShift
    {
        $day = Carbon::now()->subDays(60);
        $factory = Event::factory()
            ->asClosed()
            ->withEventStart($day->copy()->setTime(10, 0))
            ->withEventEnd($day->copy()->setTime(14, 0));

        $event = ($coordinator ? $factory->withCreatedByTrooper($coordinator) : $factory)
            ->create([Event::NAME => $name]);

        return EventShift::factory()->forEvent($event)->asClosed()
            ->withShiftStartsAt($day->copy()->setTime(10, 0))
            ->withShiftEndsAt($day->copy()->setTime(14, 0))
            ->create();
    }

    protected function requestCorrection(Trooper $trooper, Event $event): TroopCorrection
    {
        return RequestTroopCorrection::call(
            trooper: $trooper,
            event: $event,
            details: 'I held the door for the parade.',
        );
    }
}
