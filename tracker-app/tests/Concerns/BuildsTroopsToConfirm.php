<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;

/**
 * A sign-up on a shift that has closed and ended: what "did you make it?" asks about.
 */
trait BuildsTroopsToConfirm
{
    protected function troopToConfirm(
        Trooper $trooper,
        EventTrooperStatus $status = EventTrooperStatus::GOING,
        ?Carbon $ended_at = null,
        string $event_name = 'Holiday Parade',
    ): EventTrooper {
        $ended_at ??= now()->subDay();
        //  one club per trooper, so the costume never asks which club to credit
        $organization = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->first()
            ?->organization;

        if ($organization === null)
        {
            $organization = Organization::factory()->create();
            TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        }

        $event = Event::factory()->withOrganization($organization)->create([
            Event::NAME => $event_name,
            Event::STATUS => EventStatus::CLOSED,
            Event::EVENT_START => $ended_at->copy()->subHours(4),
            Event::EVENT_END => $ended_at,
        ]);

        $shift = EventShift::factory()->forEvent($event)->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_STARTS_AT => $ended_at->copy()->subHours(4),
            EventShift::SHIFT_ENDS_AT => $ended_at,
        ]);

        return EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create([
            EventTrooper::STATUS => $status,
        ]);
    }
}
