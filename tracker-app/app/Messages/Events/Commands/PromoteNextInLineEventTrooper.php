<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;
use App\Services\EventRosterCapacityService;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Command to promote the next standby trooper when someone cancels.
 *
 * When a trooper cancels their event sign-up, this command finds the next
 * trooper on standby (STAND_BY status) for the same shift and promotes them
 * to GOING status, sending them a notification email.
 *
 * @method static void call(EventTrooper $event_trooper, bool $global_was_full = false, ?int $effective_org_id = null, ?bool $override_is_handler = null, ?int $event_shift_station_id = null)
 */
final class PromoteNextInLineEventTrooper extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper who cancelled (used to find their shift).
     */
    private EventRosterCapacityService $capacity;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly bool $global_was_full = false,
        private readonly ?int $effective_org_id = null,
        private readonly ?bool $override_is_handler = null,
        private readonly ?int $event_shift_station_id = null,
    ) {}

    public function handle(EventRosterCapacityService $capacity): void
    {
        $this->capacity = $capacity;

        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;

        // When is_handler was just changed on the model, use the override to search
        // the correct (old) pool rather than the newly-set value.
        $is_handler = $this->override_is_handler ?? $event_trooper->is_handler;

        // Use the explicit org if set, otherwise use the effective org inferred
        // from the costume (passed by the caller or resolved here).
        $org_id = $this->effective_org_id
            ?? $event_trooper->organization_id
            ?? $event_trooper->effectiveOrgId($event_shift->event);

        $next_in_line = null;

        if ($this->event_shift_station_id !== null)
        {
            $next_in_line = $this->firstEligible(
                $event_shift,
                $this->stationPool($event_shift, $this->event_shift_station_id),
            );
        }

        // Prefer the next STAND_BY trooper from the same organization when the
        // departing trooper held an org-limited slot. Also match troopers whose
        // costume org infers the same org (organization_id may be null).
        if ($this->event_shift_station_id === null && $next_in_line === null && $org_id !== null)
        {
            $next_in_line = $this->firstEligible(
                $event_shift,
                $this->organizationPool($event_shift, $is_handler, $org_id),
            );
        }

        // Fall back to any STAND_BY with the same role when the global event
        // capacity was also full, or when there was no org context at all.
        if ($this->event_shift_station_id === null && $next_in_line === null
            && ($this->global_was_full || $org_id === null))
        {
            $next_in_line = $this->firstEligible(
                $event_shift,
                $this->rolePool($event_shift, $is_handler),
            );
        }

        if ($next_in_line !== null)
        {
            $next_in_line->status = EventTrooperStatus::GOING;
            $next_in_line->save();

            $next_in_line->trooper->notify(new TrooperPromotedToGoingNotification($next_in_line));
        }
    }

    /**
     * @param  Collection<int, EventTrooper>  $candidates
     */
    private function firstEligible(EventShift $event_shift, Collection $candidates): ?EventTrooper
    {
        return $candidates->first(function (EventTrooper $candidate) use ($event_shift)
        {
            if ($candidate->needsCostumeBeforeGoing())
            {
                return false;
            }

            $candidate_org_id = $candidate->organization_id
                ?? $candidate->effectiveOrgId($event_shift->event);

            return $this->capacity->canGo(
                $event_shift,
                $candidate_org_id,
                $candidate->event_shift_station_id,
                $candidate->is_handler,
            );
        });
    }

    private function standbyQuery(EventShift $event_shift): HasMany
    {
        return $event_shift->event_troopers()
            ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
            ->orderBy(EventTrooper::SIGNED_UP_AT)
            ->orderBy(EventTrooper::ID);
    }

    /**
     * @return Collection<int, EventTrooper>
     */
    private function stationPool(EventShift $event_shift, int $event_shift_station_id): Collection
    {
        return $this->standbyQuery($event_shift)
            ->where(EventTrooper::EVENT_SHIFT_STATION_ID, $event_shift_station_id)
            ->get();
    }

    /**
     * @return Collection<int, EventTrooper>
     */
    private function organizationPool(EventShift $event_shift, bool $is_handler, int $org_id): Collection
    {
        return $this->standbyQuery($event_shift)
            ->where(EventTrooper::IS_HANDLER, $is_handler)
            ->where(function ($q) use ($org_id)
            {
                $q->where(EventTrooper::ORGANIZATION_ID, $org_id)
                    ->orWhereJsonContains(EventTrooper::COSTUME_ORGANIZATION_IDS, $org_id);
            })
            ->get();
    }

    /**
     * @return Collection<int, EventTrooper>
     */
    private function rolePool(EventShift $event_shift, bool $is_handler): Collection
    {
        return $this->standbyQuery($event_shift)
            ->where(EventTrooper::IS_HANDLER, $is_handler)
            ->get();
    }
}
