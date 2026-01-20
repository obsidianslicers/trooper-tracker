<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;

final class EventDisplayAssembler
{
    public static function assembleEventShift(EventShift $event_shift, Trooper $trooper): EventShift
    {
        foreach ($event_shift->event_troopers as $event_trooper)
        {
            $event_trooper->event_shift = $event_shift;

            if ($event_trooper->canUpdateCostume($event_shift, $trooper))
            {
                $event_trooper->costumes = $event_trooper->getCostumes();
            }
        }

        return $event_shift;
    }

    public static function assembleEvent(Event $event, Trooper $trooper): Event
    {
        foreach ($event->event_shifts as $event_shift)
        {
            $event_shift->event = $event;

            static::assembleEventShift($event_shift, $trooper);
        }

        return $event;
    }
}
