<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
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
     * Initializes the assembler by loading all "ofTypeOrganizations" into a keyed collection.
     *
     * Must be called from the parent class constructor to populate $this->organizations
     * before any assembly or transformation method is invoked.
     */
    private function bootHasEventDisplayAssembler(): void
    {
        $this->organizations = Organization::ofTypeOrganizations()->pluck('name', 'id');
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
        $event_shift->event_troopers->transform(fn ($et) => $this->transformEventTrooper($et));
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

        $event_trooper->costume_organizations = $this->buildDisplayOrganizations($event_trooper, $potential_orgs, $event_trooper->costume_id);

        $potential_orgs = collect($event_trooper->backup_costume_organization_ids ?? []);

        $event_trooper->backup_costume_organizations = $this->buildDisplayOrganizations($event_trooper, $potential_orgs, $event_trooper->backup_costume_id);

        return $event_trooper;
    }

    /**
     * Builds a display-friendly organization listing for a trooper's costume in an event.
     *
     * Validates costume approval by:
     * 1. Finding trooper_costumes where organization_costume relationship matches the event_trooper's costume_id (or backup_costume_id)
     * 2. Extracting organization_ids from approved costumes
     * 3. Intersecting with potential_orgs IDs to show only approved + eligible organizations
     * 4. Resolving organization names via $this->organizations keyed collection
     *
     * Rendering:
     * - Multiple orgs: prepends "(*) " indicator
     * - No matches: shows "(unattached)"
     * - Otherwise: comma-separated, sorted organization names
     *
     * @param  EventTrooper  $event_trooper  The trooper assignment (provides access to costume_id and approved costumes)
     * @param  Collection<int, int|string>  $potential_orgs  Organization IDs that were potentially selected for this costume
     * @param  int|null  $costume_id  The costume ID to use for filtering approved organizations
     * @return string Display string ready for view rendering
     */
    private function buildDisplayOrganizations(EventTrooper $event_trooper, Collection $potential_orgs, $costume_id): string
    {
        // Filter actual approvals by reaching through to the organization_costume
        $approved_orgs = $event_trooper->trooper?->trooper_costumes
            ->filter(fn ($tc) => optional($tc->organization_costume)->costume_id == $costume_id)
            ->pluck('organization_costume.organization_id')
            ->unique()
            ?? collect();

        $final_orgs = $potential_orgs->intersect($approved_orgs);

        $names = $final_orgs->map(fn ($id) => $this->organizations[$id] ?? '??')->sort();

        $prefix = $names->count() > 1 ? '(*) ' : '';
        $name_list = $names->isEmpty() ? '(unattached)' : $names->implode(', ');

        return "{$prefix}{$name_list}";
    }
}
