<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\EventShift;
use App\Models\EventTrooper;

trait HasEventOrganizationScopes
{
    public function scopePluckCanAttend($query, EventShift $event_shift)
    {
        return $query->where(self::CAN_ATTEND, true)
            ->withCount(['troopers as troopers_count' => function ($q) use ($event_shift): void
            {
                $q->where(EventTrooper::EVENT_SHIFT_ID, $event_shift->id);
            }])
            ->get()
            ->filter(fn($e_org) => $e_org->troopers_allowed === null || $e_org->troopers_count < $e_org->troopers_allowed)
            ->pluck('organization_id');
    }
}

