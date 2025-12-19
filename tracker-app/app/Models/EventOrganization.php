<?php

namespace App\Models;

use App\Models\Base\EventOrganization as BaseEventOrganization;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Represents the relationship between an event and an organization.
 *
 * This model tracks which organizations are associated with an event and whether
 * members of that organization are allowed to attend. It enables fine-grained
 * control over event participation by organization membership.
 */
class EventOrganization extends BaseEventOrganization
{
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get all troopers associated with this event through event shifts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function troopers(): HasManyThrough
    {
        // hasManyThrough: EventOrganization -> Event -> EventShift -> EventTrooper
        return $this->hasManyThrough(
            EventTrooper::class,   // final model
            EventShift::class,     // intermediate model
            'event_id',            // FK on EventShift pointing to Event
            'event_shift_id',      // FK on EventTrooper pointing to EventShift
            'event_id',            // local key on EventOrganization pointing to Event
            'id'                   // local key on EventShift
        );

    }
}
