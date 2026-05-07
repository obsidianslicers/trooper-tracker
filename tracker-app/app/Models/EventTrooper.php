<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventTrooperStatus;
use App\Models\Base\EventTrooper as BaseEventTrooper;
use App\Models\Concerns\HasAuditTrail;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventTrooperScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Event;
use App\Models\EventOrganization;

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
    use HasAuditTrail;
    use HasObserver;

    /**
     * Define the model attributes should be audited for changes.
     *
     * @return array<int, string> Array of attribute names to audit.
     */
    protected function audits(): array
    {
        return [
            self::STATUS,
        ];
    }

    /**
     * Get a human-readable label for the trooper.
     *
     * @return string The label representing the trooper.
     */
    public function getAuditLabel(): string
    {
        return $this->event_shift->event->name . ' (' . $this->event_shift->time_display . ')';
    }

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
        return $this->belongsTo(Costume::class, self::BACKUP_COSTUME_ID);
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

        $organization_ids = $this->organization_id !== null
            ? collect([$this->organization_id])
            : $this->event_shift->event->event_organizations()->pluckCanAttend($event_shift);

        return Costume::forTrooper($this->trooper->id, $organization_ids)
            ->pluck('name', 'id')
            ->sortBy(function ($name, $id)
            {
                if ($name === Costume::COMMAND_STAFF)
                {
                    return 2;
                }
                if ($name === Costume::HANDLER)
                {
                    return 1;
                }
                return 0; // Everything else stays at the top
            })
            ->toArray();
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
                //  if re-activating a cancelled friend, check the adder's friend limit
                if ($this->status === EventTrooperStatus::CANCELLED && $this->added_by_trooper_id !== null)
                {
                    $friends_allowed = $event_shift->event->friends_allowed;

                    if ($friends_allowed !== null)
                    {
                        $active_friends = $event_shift->event_troopers()
                            ->where(self::ADDED_BY_TROOPER_ID, $this->added_by_trooper_id)
                            ->where(self::STATUS, '!=', EventTrooperStatus::CANCELLED)
                            ->count();

                        if ($active_friends >= $friends_allowed)
                        {
                            return false;
                        }
                    }
                }

                if ($this->is_handler)
                {
                    if ($event_shift->handlersMaxed())
                    {
                        return false;
                    }

                    if ($this->organization_id !== null && $event_shift->orgTroopersMaxed($this->organization_id, true))
                    {
                        return false;
                    }

                    return true;
                }

                if ($event_shift->troopersMaxed())
                {
                    return false;
                }

                if ($this->organization_id !== null && $event_shift->orgTroopersMaxed($this->organization_id, false))
                {
                    return false;
                }

                return true;
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

    // Cancelling never requires a free slot, so no capacity check here.
    public function canCancel(EventShift $event_shift, Trooper $trooper): bool
    {
        return $event_shift->is_open && $this->hasOwnership($trooper);
    }

    // Capacity determines GOING vs STAND_BY at re-signup time, not whether re-signup is allowed.
    public function canReSignUp(EventShift $event_shift, Trooper $trooper): bool
    {
        return $this->status === EventTrooperStatus::CANCELLED
            && $event_shift->is_open
            && $this->hasOwnership($trooper);
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
        if ($this->trooper_id == $trooper->id || $this->added_by_trooper_id == $trooper->id)
        {
            return true;
        }

        return $this->trooper->guardian_id === $trooper->id;
    }

    /**
     * Resolve the effective organization for capacity-limit purposes.
     *
     * Returns the explicit organization_id if set. Otherwise infers it from
     * costume_organization_ids: if the costume belongs to exactly one per-org-limited
     * organization on this event, that org is returned. Used so that per-org limits
     * apply even when the trooper never explicitly chose an organization.
     */
    public function effectiveOrgId(Event $event): ?int
    {
        if ($this->organization_id !== null)
        {
            return $this->organization_id;
        }

        $costume_org_ids = $this->costume_organization_ids ?? [];

        if (empty($costume_org_ids))
        {
            return null;
        }

        $event->loadMissing('event_organizations');

        $limited_org_ids = $event->event_organizations
            ->filter(fn ($eo) => $eo->troopers_allowed !== null || $eo->handlers_allowed !== null)
            ->pluck(EventOrganization::ORGANIZATION_ID)
            ->all();

        $matches = array_intersect($costume_org_ids, $limited_org_ids);

        return count($matches) === 1 ? reset($matches) : null;
    }
}
