<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Support\Collection;

/**
 * Assembles event display data for views by enriching queries with computed properties and nested data.
 *
 * This trait provides functionality to:
 * - Load and transform event shifts with trooper assignments
 * - Calculate available costumes for each trooper in a shift
 * - Build display-friendly organization listings for event troopers
 * - Render organization names with multi-organization indicators
 *
 * Used by EventQuery handlers to prepare event data for display in templates.
 * Traits using this must call bootHasEventDisplayAssembler() in their constructor.
 *
 * @see EventTrooper
 * @see EventShift
 */
trait HasEventDisplayAssembler
{
    /**
     * Keyed collection of organization names.
     * Maps organization ID to organization name for display assembly.
     *
     * @var Collection<int, string>
     */
    private readonly Collection $organizations;

    /**
     * Per-trooper cache of active membership organization IDs, keyed by trooper ID.
     * Populated lazily by memberOrgIds() to avoid re-querying for the same trooper.
     * Held as a Collection (mutated in place via put()) so it stays compatible with
     * readonly-class handlers that use this trait — only the reference is readonly.
     *
     * @var Collection<int, Collection<int, int>>
     */
    private readonly Collection $member_org_ids_cache;

    /**
     * Initializes the assembler by loading all "ofTypeOrganizations" into a keyed collection.
     *
     * Must be called from the parent class constructor to populate $this->organizations
     * before any assembly or transformation method is invoked.
     */
    private function bootHasEventDisplayAssembler(): void
    {
        $this->organizations = Organization::ofTypeOrganizations()->pluck('name', 'id');
        $this->member_org_ids_cache = collect();
    }

    /**
     * Resolves the organization IDs the trooper currently belongs to (active membership only).
     *
     * A trooper can hold a costume approval for an org they've since left, or for an org
     * offering a shared/command-staff costume they were never a member of — this scopes
     * display down to orgs they actually belong to, cached per trooper to avoid N+1 queries.
     *
     * @param  Trooper  $trooper  The trooper whose active memberships to resolve
     * @return Collection<int, int> Organization IDs the trooper is an active member of
     */
    private function memberOrgIds(Trooper $trooper): Collection
    {
        if (!$this->member_org_ids_cache->has($trooper->id))
        {
            $this->member_org_ids_cache->put(
                $trooper->id,
                $trooper->organizations()
                    ->wherePivotNull(TrooperOrganization::DELETED_AT)
                    ->get()
                    ->pluck(Organization::ID)
            );
        }

        return $this->member_org_ids_cache->get($trooper->id);
    }

    /**
     * Ensures each event has a complete organization list for display.
     *
     * Any organization not currently attached to an event is appended with a
     * synthetic pivot object where can_attend is false.
     *
     * @param  Collection<int, Event>  $events  Events to enrich in place
     */
    private function assembleEventOrganizations(Collection $events): void
    {
        foreach ($events as $event)
        {
            $this->assembleEventOrganization($event);
        }
    }

    /**
     * Appends missing organizations to a single event for display parity.
     *
     * This guarantees all known organizations are present in the event's
     * organizations collection, with unavailable entries flagged via pivot.
     *
     * @param  Event  $event  Event to enrich
     */
    private function assembleEventOrganization(Event $event): void
    {
        $event_org_ids = $event->organizations->pluck(Organization::ID)->all();
        $missing_orgs = $this->organizations->except($event_org_ids);

        foreach ($missing_orgs as $id => $name)
        {
            $org_clone = new Organization([
                Organization::ID => $id,
                Organization::NAME => $name,
            ]);
            $org_clone->pivot = (object) ['can_attend' => false];
            $event->organizations->push($org_clone);
        }

        $event->organizations = $event->organizations->sortBy(Organization::NAME)->values();
    }

    /**
     * Assembles costume selection data for event troopers within a single shift.
     *
     * For each trooper assignment in the shift, sets up bidirectional event_shift reference
     * and conditionally loads available costumes via getCostumes() if the trooper can update
     * their costume assignment for this shift.
     *
     * @param  EventShift  $event_shift  The event shift containing trooper assignments
     * @param  Trooper  $trooper  The authenticated trooper (used for permission checks)
     * @return EventShift The shift with assembled trooper data and costume options
     */
    private function assembleEventShift(EventShift $event_shift, Trooper $trooper): EventShift
    {
        foreach ($event_shift->event_troopers as $event_trooper)
        {
            $event_trooper->event_shift = $event_shift;

            if ($event_trooper->canUpdateCostume($trooper))
            {
                $event_trooper->costumes = $event_trooper->getCostumes();
            }
        }

        return $event_shift;
    }

    /**
     * Assembles costume selection data for all shifts within an event.
     *
     * Iterates through each event shift, sets up bidirectional event reference,
     * and delegates to assembleEventShift() for shift-specific trooper data assembly.
     *
     * @param  Event  $event  The event containing shifts and trooper assignments
     * @param  Trooper  $trooper  The authenticated trooper (used for permission checks)
     * @return Event The event with fully assembled shift and trooper data
     */
    private function assembleEvent(Event $event, Trooper $trooper): Event
    {
        foreach ($event->event_shifts as $event_shift)
        {
            $event_shift->event = $event;

            $this->assembleEventShift($event_shift, $trooper);
        }

        return $event;
    }

    /**
     * Transforms event troopers within a shift by building display-friendly organization listings.
     *
     * Applies transformEventTrooper() to each trooper in the shift's collection,
     * adding computed display properties for both primary and backup costume organizations.
     *
     * @param  EventShift  $event_shift  The shift containing trooper assignments to transform
     * @return void Modifies event_troopers in place via transform callback
     */
    private function transformEventShift(EventShift $event_shift): void
    {
        $event_shift->event_troopers->transform(fn($et) => $this->transformEventTrooper($et));
    }

    /**
     * Transforms an event trooper by building organization display strings.
     *
     * Processes both costume_organization_ids and backup_costume_organization_ids,
     * creating display strings that:
     * - Intersect potential organization IDs (from event trooper record) with approved IDs
     *   (from trooper's costume_costumes matching the event_trooper's costume_id)
     * - Show organization names in sorted order
     * - Prefix with "(*) " when multiple organizations are selected
     * - Show "(unattached)" when no approved organizations remain
     *
     * @param  EventTrooper  $event_trooper  The trooper assignment to transform
     * @return EventTrooper The trooper with computed costume_organizations and backup_costume_organizations properties
     */
    private function transformEventTrooper(EventTrooper $event_trooper): EventTrooper
    {
        $potential_orgs = collect($event_trooper->costume_organization_ids ?? []);

        $event_trooper->costume_organizations = $this->buildDisplayOrganizations($event_trooper, $potential_orgs, $event_trooper->costume);

        $potential_orgs = collect($event_trooper->backup_costume_organization_ids ?? []);

        $event_trooper->backup_costume_organizations = $this->buildDisplayOrganizations($event_trooper, $potential_orgs, $event_trooper->backup_costume);

        return $event_trooper;
    }

    /**
     * Builds a display-friendly organization listing for a trooper's costume in an event.
     *
     * Eligibility is resolved by resolveApprovedOrganizations() (costume approvals or, for
     * handler/Command-Staff costumes, live membership) and narrowed by potential_orgs. Two
     * cases override that eligibility narrowing with a specific, already-decided value:
     * - An explicit org slot (event_trooper->organization_id, set when the event has per-org
     *   capacity limits) reflects an actual trooper decision, so it is shown directly.
     * - Once attendance is confirmed (status ATTENDED), organization_id/potential_orgs IS the
     *   credited record — it is shown as-is rather than re-filtered against current
     *   approved_orgs, which reflects live eligibility and can have shifted since the event
     *   (e.g. the trooper later left the credited club).
     *
     * Rendering:
     * - Multiple orgs: prepends "(*) " indicator
     * - No matches: shows "(unattached)"
     * - Otherwise: comma-separated, sorted organization names
     *
     * @param  EventTrooper  $event_trooper  The trooper assignment (provides access to approved costumes)
     * @param  Collection<int, int|string>  $potential_orgs  Organization IDs that were potentially selected for this costume
     * @param  Costume|null  $costume  The costume worn (primary or backup); null means no costume selected
     * @return string Display string ready for view rendering
     */
    private function buildDisplayOrganizations(EventTrooper $event_trooper, Collection $potential_orgs, ?Costume $costume): string
    {
        $approved_orgs = $this->resolveApprovedOrganizations($event_trooper, $costume);
        $potential_orgs = $this->normalizePotentialOrganizations($potential_orgs, $costume);

        if ($event_trooper->organization_id !== null
            && ($event_trooper->attended || $approved_orgs->contains($event_trooper->organization_id)))
        {
            return $this->organizations[$event_trooper->organization_id] ?? '??';
        }

        $final_orgs = match (true)
        {
            $event_trooper->attended && $potential_orgs->isNotEmpty() => $potential_orgs,
            $potential_orgs->isEmpty() => $approved_orgs,
            default => $potential_orgs->intersect($approved_orgs),
        };

        $names = $final_orgs->map(fn($id) => $this->organizations[$id] ?? '??')->sort();

        $prefix = $names->count() > 1 ? '(*) ' : '';
        $name_list = $names->isEmpty() ? '(unattached)' : $names->implode(', ');

        return "{$prefix}{$name_list}";
    }

    /**
     * Resolves the organization IDs the trooper is eligible for, given the costume worn.
     *
     * For handler/Command-Staff costumes (or no costume selected), credit derives from
     * membership rather than costume offerings — see Costume::countsAsHandler(). In that
     * case the result is the trooper's active trooper_assignments membership, rolled up to
     * primary-club IDs (Trooper::activeAssignmentPrimaryClubIds()) to match the top-level
     * clubs $this->organizations is keyed by.
     *
     * Otherwise, credit is membership-driven rather than requiring a personal costume
     * approval record: it's the orgs that offer this costume (Costume::organization_costumes,
     * i.e. the org's costume catalog) intersected with orgs the trooper is an active member
     * of (memberOrgIds()) — a trooper who belongs to a club that offers this costume type
     * should show as attached to it even if no individual trooper_costume approval row was
     * ever recorded for them.
     *
     * @return Collection<int, int>
     */
    private function resolveApprovedOrganizations(EventTrooper $event_trooper, ?Costume $costume): Collection
    {
        if ($event_trooper->trooper === null)
        {
            return collect();
        }

        if ($costume === null || $costume->countsAsHandler())
        {
            return $event_trooper->trooper->activeAssignmentPrimaryClubIds();
        }

        $costume_org_ids = $costume->organization_costumes
            ->pluck(OrganizationCostume::ORGANIZATION_ID)
            ->unique();

        return $costume_org_ids->intersect($this->memberOrgIds($event_trooper->trooper));
    }

    /**
     * Rolls up a stored potential_orgs snapshot to primary-club IDs for handler/Command-Staff
     * costumes, since it can hold sub-org IDs from a prior save (see
     * EventTrooper::getEligibleCreditOrganizations()) that won't match $this->organizations,
     * which is keyed by top-level clubs only.
     *
     * @param  Collection<int, int|string>  $potential_orgs
     * @return Collection<int, int|string>
     */
    private function normalizePotentialOrganizations(Collection $potential_orgs, ?Costume $costume): Collection
    {
        if ($costume === null || $costume->countsAsHandler())
        {
            return Organization::rootIdsFor($potential_orgs);
        }

        return $potential_orgs;
    }
}
