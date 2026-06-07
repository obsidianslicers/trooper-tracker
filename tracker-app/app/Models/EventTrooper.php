<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\Base\EventTrooper as BaseEventTrooper;
use App\Models\Concerns\HasAuditTrail;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventTrooperScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Support\Facades\Crypt;

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
     * @return array<string, string> Map of attribute names to cast types.
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Backup costume relation.
     */
    public function backup_costume(): BelongsTo
    {
        return $this->belongsTo(Costume::class, self::BACKUP_COSTUME_ID);
    }

    /**
     * Get the trooper who added this event trooper assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Adding trooper relation.
     */
    public function added_by_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::ADDED_BY_TROOPER_ID);
    }

    /**
     * Check if the trooper attended the event.
     *
     * @return bool True when status is ATTENDED.
     */
    public function getAttendedAttribute(): bool
    {
        return $this->status === EventTrooperStatus::ATTENDED;
    }

    /**
     * Check if the trooper is on stand-by for the event.
     *
     * @return bool True when status is STAND_BY.
     */
    public function getIsStandByAttribute(): bool
    {
        return $this->status === EventTrooperStatus::STAND_BY;
    }

    /**
     * Retrieves available costume options for this event trooper assignment.
     *
     * Returns costumes keyed by costume ID and valued by costume name.
     * The result is filtered by eligible organizations for the shift.
     *
     * @return array<int|string, string>
     */
    public function getCostumes(): array
    {
        if ($this->trooper->membership_role === MembershipRole::HANDLER)
        {
            return Costume::where('name', Costume::HANDLER)->pluck('name', 'id')->toArray();
        }

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
     * Check if the trooper is going to the event.
     *
     * @return bool True when status is GOING.
     */
    public function intendsToGo(): bool
    {
        /** @var EventTrooperStatus $this->status */
        return $this->status->intendsToGo();
    }

    /**
     * Determines whether attendance can be marked for this assignment.
     *
     * Attendance can be marked only when the shift is closed, the event allows
     * trooper status updates, the caller has ownership, and current status is GOING.
     *
     * @param Trooper $actor The trooper attempting the update
     * @return bool True if the status can be updated
     */
    public function canMarkAttendance(Trooper $actor): bool
    {
        if ($this->event_shift->is_closed && $this->event_shift->event->can_update_trooper_status && $this->hasOwnership($actor))
        {
            if ($this->intendsToGo())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the status for this event trooper assignment can be updated.
     *
     * Status can be updated if the shift is open and the trooper has ownership.
     * If changing from a non-going status, the shift must not be at capacity.
     *
     * @param Trooper $actor The trooper attempting the update
     * @return bool True if the status can be updated
     */
    public function canUpdateStatus(Trooper $actor): bool
    {
        if ($this->event_shift->is_open && $this->hasOwnership($actor))
        {
            //  if they aren't going (ie cancelled, or tenative),
            //  and it's full they can't set to something else
            if ($this->status != EventTrooperStatus::GOING)
            {
                //  if re-activating a cancelled friend, check the adder's friend limit
                if ($this->status === EventTrooperStatus::CANCELLED && $this->added_by_trooper_id !== null)
                {
                    $friends_allowed = $this->event_shift->event->friends_allowed;

                    if ($friends_allowed !== null)
                    {
                        $active_friends = $this->event_shift->event_troopers()
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
                    if ($this->event_shift->handlersMaxed())
                    {
                        return false;
                    }

                    if ($this->organization_id !== null && $this->event_shift->orgTroopersMaxed($this->organization_id, true))
                    {
                        return false;
                    }

                    return true;
                }

                if ($this->event_shift->troopersMaxed())
                {
                    return false;
                }

                if ($this->organization_id !== null && $this->event_shift->orgTroopersMaxed($this->organization_id, false))
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
     * Determines whether the costume can be updated for this assignment.
     *
     * Costume updates are allowed when the shift is open and the caller has ownership,
     * or during grace period when the shift is closed and status is GOING.
     *
     * @param Trooper $actor The trooper attempting the update
     * @return bool True if the costume can be updated
     */
    public function canUpdateCostume(Trooper $actor): bool
    {
        if ($this->event_shift->is_open)
        {
            return $this->hasOwnership($actor);
        }

        if ($this->event_shift->is_closed && $this->event_shift->event->is_within_grace_period)
        {
            if ($this->intendsToGo() && $this->hasOwnership($actor))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the assignment can be cancelled by the given trooper.
     *
     * @param Trooper $actor The trooper attempting cancellation.
     * @return bool True when the shift is open and the actor has ownership.
     */
    public function canCancel(Trooper $actor): bool
    {
        return $this->event_shift->is_open && $this->hasOwnership($actor);
    }

    /**
     * Determines whether the assignment can be re-signed up by the given trooper.
     *
     * @param Trooper $actor The trooper attempting re-signup.
     * @return bool True when status is CANCELLED, shift is open, and actor has ownership.
     */
    public function canReSignUp(Trooper $actor): bool
    {
        return $this->status === EventTrooperStatus::CANCELLED
            && $this->event_shift->is_open
            && $this->hasOwnership($actor);
    }

    /**
     * Check if a trooper has ownership of this event trooper assignment.
     *
     * A trooper has ownership if they are the assigned trooper or the one who added the assignment.
     *
     * @param Trooper $actor The trooper to check
     * @return bool True if the trooper has ownership
     */
    private function hasOwnership(Trooper $actor): bool
    {
        if ($this->trooper_id == $actor->id || $this->added_by_trooper_id == $actor->id)
        {
            return true;
        }

        return $this->trooper->guardian_id === $actor->id;
    }

    /**
     * Build the shift-complete URL with an encrypted attendance status token.
     *
     * @param EventTrooperStatus $status The attendance status to submit.
     * @return string The URL for attendance confirmation.
     */
    public function getAttendanceUrl(EventTrooperStatus $status): string
    {
        return route('events.shift-complete', ['event_trooper' => $this, 'status' => Crypt::encryptString($status->value)]);
    }

    /**
     * Returns organizations eligible to receive troop credit for this shift.
     *
     * @return Collection<int, Organization>
     */
    public function getEligibleCreditOrganizations(): Collection
    {
        // Handler/Command Staff credit derives from membership, not costume approvals.
        // costume_organization_ids is filtered to the event's can_attend orgs for capacity
        // tracking, but credit selection must see the full membership so multi-club handlers
        // are offered the club-select form.
        $this->loadMissing('costume');
        if ($this->costume?->countsAsHandler())
        {
            return Organization::whereHas('trooper_assignments', fn($q) =>
                $q->where(TrooperAssignment::TROOPER_ID, $this->trooper_id)
                    ->where(TrooperAssignment::IS_MEMBER, true)
            )->get();
        }

        $costume_org_ids = $this->costume_organization_ids ?? [];

        if (!empty($costume_org_ids))
        {
            return Organization::whereIn('id', $costume_org_ids)
                ->whereHas('trooper_assignments', fn($q) =>
                    $q->where(TrooperAssignment::TROOPER_ID, $this->trooper_id)
                        ->where(TrooperAssignment::IS_MEMBER, true)
                )
                ->get();
        }

        return Organization::whereHas('trooper_assignments', fn($q) =>
            $q->where(TrooperAssignment::TROOPER_ID, $this->trooper_id)
                ->where(TrooperAssignment::IS_MEMBER, true)
        )->get();
    }

    /**
     * Returns unique top-level organizations for eligible troop credit.
     *
     * @return Collection<int, Organization> Unique primary-club organizations.
     */
    public function getEligibleCreditParentOrganizations(): Collection
    {
        return $this->getEligibleCreditOrganizations()
            ->map(fn($org) => $org->getPrimaryClub())
            ->unique('id')
            ->values();
    }

    /**
     * Maps selected top-level organization IDs to eligible child organization IDs.
     *
     * @param array<int, int> $parent_org_ids Selected primary-club organization IDs.
     * @return array<int, int> Eligible child organization IDs for those primary clubs.
     */
    public function childOrgIdsForSelectedParents(array $parent_org_ids): array
    {
        return $this->getEligibleCreditOrganizations()
            ->filter(fn($org) => in_array($org->getPrimaryClub()->id, $parent_org_ids, true))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * Returns unique top-level organization names receiving troop credit.
     *
     * @return array<int, string> Sorted unique primary-club names.
     */
    public function getCreditedRootOrgNames(): array
    {
        $ids = $this->costume_organization_ids ?? [];

        if (empty($ids) && $this->organization_id !== null)
        {
            $ids = [$this->organization_id];
        }

        if (empty($ids))
        {
            return [];
        }

        return Organization::whereIn('id', $ids)
            ->get()
            ->map(fn($org) => $org->getPrimaryClub()->name)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Resolve the effective organization for capacity-limit purposes.
     *
     * Returns the explicit organization_id if set. Otherwise infers it from
     * costume_organization_ids: if the costume belongs to exactly one per-org-limited
     * organization on this event, that org is returned. Used so that per-org limits
     * apply even when the trooper never explicitly chose an organization.
     *
     * @param Event $event Event context used to inspect per-organization limits.
     * @return int|null Effective organization ID, or null when it is ambiguous.
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
            ->filter(fn($eo) => $eo->troopers_allowed !== null || $eo->handlers_allowed !== null)
            ->pluck(EventOrganization::ORGANIZATION_ID)
            ->all();

        $matches = array_intersect($costume_org_ids, $limited_org_ids);

        return count($matches) === 1 ? reset($matches) : null;
    }
}
