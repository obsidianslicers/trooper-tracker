<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Support\Collection;

/**
 * Handler for enriching event shifts with trooper and costume data.
 *
 * Retrieves all shifts for an event with related troopers and costumes,
 * then transforms the data to compute organization/club display names
 * for administrative display.
 *
 * @implements QueryHandlerInterface<GetTroopersForEventAdminQuery>
 */
readonly class GetTroopersForEventAdminQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    /**
     * Execute the query to retrieve and enrich event shifts with trooper data.
     *
     * Process:
     * 1. Retrieve all shifts for the event
     * 2. Eager load related troopers, costumes, and assignments
     * 3. Transform troopers to compute costume_organizations for each EventTrooper
     * 4. Return enriched collection of EventShift models
     *
     * @param  GetTroopersForEventAdminQuery  $message  The query containing the event.
     * @return Collection<int, EventShift> Event shifts with enriched trooper data.
     */
    public function __invoke(object $message): mixed
    {
        $event_shifts = $message->event->event_shifts()
            ->with($this->buildRelations())
            ->orderBy(EventShift::SHIFT_STARTS_AT)
            ->get();

        $event_shifts->each(fn ($shift) => $this->transformEventShift($shift));

        $this->decorateEventTroopersForRosterForm($event_shifts, $message->allowed_org_ids);

        return $event_shifts;
    }

    /**
     * Adds the editable costume/org option lists the roster edit form renders per trooper.
     *
     * Always includes the trooper's currently-stored costume/credit as a selectable option,
     * even when it's no longer "live eligible" (e.g. a costume approval or club membership
     * changed since they attended) — otherwise the form silently drops that value on save.
     */
    private function decorateEventTroopersForRosterForm(Collection $event_shifts, ?array $allowed_org_ids): void
    {
        $all = $event_shifts->flatMap(fn ($s) => $s->event_troopers);

        $costume_ids = $all->pluck('costume_id')->filter()->unique()->values()->all();
        $costumes_by_id = Costume::findMany($costume_ids)->keyBy('id');

        $costume_options_by_trooper = $this->buildCostumeOptionsByTrooper($all);

        foreach ($event_shifts as $shift)
        {
            foreach ($shift->event_troopers as $event_trooper)
            {
                $costume_options = $costume_options_by_trooper->get($event_trooper->trooper_id, []);
                $event_trooper->costume_options = $this->includeStoredCostumeOption($event_trooper, $costume_options, $costumes_by_id);

                $costume = $costumes_by_id->get($event_trooper->costume_id);
                $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, $costume);
                $credited_ids = $event_trooper->creditedRootOrgIds();
                $event_trooper->org_options = $this->includeCreditedOrgOptions($org_options, $credited_ids);
                $event_trooper->credited_checked_ids = $credited_ids;
            }
        }
    }

    /**
     * Builds costume options from the already-eager-loaded trooper_costumes rather than
     * firing one query per trooper. Handler/Command Staff costumes are always included.
     */
    private function buildCostumeOptionsByTrooper(Collection $all): Collection
    {
        $approved_costume_ids = $all
            ->flatMap(fn ($et) => $et->trooper->trooper_costumes->pluck('organization_costume.costume_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $all_option_costumes = Costume::query()
            ->where(function ($q) use ($approved_costume_ids)
            {
                $q->whereIn(Costume::NAME, [Costume::COMMAND_STAFF, Costume::HANDLER]);
                if (!empty($approved_costume_ids))
                {
                    $q->orWhereIn('id', $approved_costume_ids);
                }
            })
            ->pluck('name', 'id');

        $handler_costume_ids = $all_option_costumes
            ->filter(fn ($name) => in_array($name, [Costume::COMMAND_STAFF, Costume::HANDLER], true))
            ->keys()
            ->toArray();

        return $all->groupBy('trooper_id')
            ->map(function ($group) use ($all_option_costumes, $handler_costume_ids)
            {
                $trooper_costume_ids = $group->first()->trooper->trooper_costumes
                    ->pluck('organization_costume.costume_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                $ids = array_unique(array_merge($trooper_costume_ids, $handler_costume_ids));

                return $all_option_costumes->only($ids)->toArray();
            });
    }

    private function includeStoredCostumeOption(EventTrooper $event_trooper, array $costume_options, Collection $costumes_by_id): array
    {
        if ($event_trooper->costume_id === null || array_key_exists($event_trooper->costume_id, $costume_options))
        {
            return $costume_options;
        }

        $stored_costume = $costumes_by_id->get($event_trooper->costume_id);

        if ($stored_costume === null)
        {
            return $costume_options;
        }

        return $costume_options + [$stored_costume->id => $stored_costume->name];
    }

    private function includeCreditedOrgOptions(Collection $org_options, array $credited_ids): Collection
    {
        $missing_ids = array_diff($credited_ids, $org_options->pluck('id')->all());

        if (empty($missing_ids))
        {
            return $org_options;
        }

        $missing_orgs = Organization::findMany($missing_ids);

        return $org_options->concat($missing_orgs)->sortBy(Organization::NAME)->values();
    }

    private function buildRelations(): array
    {
        $trooper_columns = [
            Trooper::ID,
            Trooper::DISPLAY_NAME,
            Trooper::LEGAL_NAME,
        ];

        $trooper_costume_columns = [
            TrooperCostume::ID,
            TrooperCostume::TROOPER_ID,
            TrooperCostume::ORGANIZATION_COSTUME_ID,
        ];

        $organization_costume_columns = [
            OrganizationCostume::ID,
            OrganizationCostume::COSTUME_ID,
            OrganizationCostume::ORGANIZATION_ID,
        ];

        $costume_columns = [
            Costume::ID,
            Costume::NAME,
        ];

        $with = [
            'event_shift_stations' => function ($query)
            {
                $query->withCount('going_event_troopers')
                    ->orderBy(EventShiftStation::SEQUENCE)
                    ->orderBy(EventShiftStation::NAME);
            },
            'event_troopers.trooper:'.implode(',', $trooper_columns),
            'event_troopers.updated_by:'.implode(',', $trooper_columns),
            'event_troopers.event_shift_station',
            'event_troopers.trooper.organizations',
            'event_troopers.trooper.trooper_costumes:'.implode(',', $trooper_costume_columns),
            'event_troopers.trooper.trooper_costumes.organization_costume:'.implode(',', $organization_costume_columns),
            'event_troopers.costume:'.implode(',', $costume_columns),
            'event_troopers.costume.organization_costumes:'.implode(',', $organization_costume_columns),
            'event_troopers.backup_costume:'.implode(',', $costume_columns),
            'event_troopers.backup_costume.organization_costumes:'.implode(',', $organization_costume_columns),
            'event_guests.added_by_trooper:'.implode(',', $trooper_columns),
            'event_guests.updated_by:'.implode(',', $trooper_columns),
        ];

        return $with;
    }
}
