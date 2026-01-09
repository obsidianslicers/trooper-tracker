<?php

namespace App\Models;

use App\Enums\EventTrooperStatus;
use App\Models\Base\EventTrooper as BaseEventTrooper;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventTrooperScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a trooper's participation in an event shift.
 *
 * This model tracks individual trooper assignments to specific event shifts,
 * including their costume selection, attendance status, handler designation,
 * and participation details. It serves as the junction between troopers,
 * events, and event shifts.
 */
class EventTrooper extends BaseEventTrooper
{
    use HasEventTrooperScopes;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => EventTrooperStatus::class,
        ]);
    }

    /**
     * Get the backup costume for this event trooper assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function backup_costume(): BelongsTo
    {
        return $this->belongsTo(OrganizationCostume::class, self::BACKUP_COSTUME_ID);
    }

    /**
     * Get the trooper who added this event trooper assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function added_by_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::ADDED_BY_TROOPER_ID);
    }

    /**
     * Check if the trooper attended the event.
     *
     * @return bool
     */
    public function getAttendedAttribute(): bool
    {
        return $this->status === EventTrooperStatus::ATTENDED;
    }

    /**
     * Check if the trooper is going to the event.
     *
     * @return bool
     */
    public function getIsGoingAttribute(): bool
    {
        return $this->status === EventTrooperStatus::GOING;
    }

    /**
     * Check if the trooper is on stand-by for the event.
     *
     * @return bool
     */
    public function getIsStandByAttribute(): bool
    {
        return $this->status === EventTrooperStatus::STAND_BY;
    }

    /**
     * Get available costume options for this event trooper assignment.
     *
     * Returns a list of organization costumes that the trooper can select for this
     * event shift. The list is filtered based on organizations allowed to attend
     * the event and costumes owned by or available to the trooper.
     *
     * @return array<string, mixed> Array of costume options formatted as ['name' => string, 'id' => int]
     */
    public function getCostumes(): array
    {
        $event_shift = $this->event_shift;

        $organization_ids = $this->event_shift->event->event_organizations()->pluckCanAttend($event_shift);

        return OrganizationCostume::with('organization')
            ->forEventShift($this->event_shift, $this->trooper, $organization_ids)
            ->toOptions('full_name', 'id');
    }

    /**
     * Check if the status for this event trooper assignment can be updated.
     *
     * Status can be updated if the shift is open and the trooper has ownership.
     * If changing from a non-going status, the shift must not be at capacity.
     *
     * @param EventShift $event_shift The event shift this assignment belongs to
     * @param Trooper $trooper The trooper attempting the update
     * @return bool True if the status can be updated
     */
    public function canUpdateStatus(EventShift $event_shift, Trooper $trooper): bool
    {
        if ($event_shift->is_open && $this->hasOwnership($trooper))
        {
            //  if they aren't going (ie cancelled, or tenative),
            //  and it's full they can't set to something else
            if ($this->status != EventTrooperStatus::GOING)
            {
                if ($this->is_handler)
                {
                    return !$event_shift->handlersMaxed();
                }

                return !$event_shift->troopersMaxed();
            }

            return true;
        }

        return false;
    }

    /**
     * Check if the costume for this event trooper assignment can be updated.
     *
     * Costume can be updated if the shift is open, the trooper has ownership,
     * and this is not a handler assignment.
     *
     * @param EventShift $event_shift The event shift this assignment belongs to
     * @param Trooper $trooper The trooper attempting the update
     * @return bool True if the costume can be updated
     */
    public function canUpdateCostume(EventShift $event_shift, Trooper $trooper): bool
    {
        if (!$this->is_handler && $event_shift->is_open)
        {
            return $this->hasOwnership($trooper);
        }

        return false;
    }

    /**
     * Check if a trooper has ownership of this event trooper assignment.
     *
     * A trooper has ownership if they are the assigned trooper or the one who added the assignment.
     *
     * @param Trooper $trooper The trooper to check
     * @return bool True if the trooper has ownership
     */
    private function hasOwnership(Trooper $trooper): bool
    {
        return $this->trooper_id == $trooper->id || $this->added_by_trooper_id == $trooper->id;
    }
}
