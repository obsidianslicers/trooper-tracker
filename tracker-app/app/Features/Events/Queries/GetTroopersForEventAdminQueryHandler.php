<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\EventShiftStation;
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

        return $event_shifts;
    }

    private function buildRelations(): array
    {
        $trooper_columns = [
            Trooper::ID,
            Trooper::DISPLAY_NAME,
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
            'event_shift_stations' => function ($query) {
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
            'event_troopers.backup_costume:'.implode(',', $costume_columns),
            'event_guests.added_by_trooper:'.implode(',', $trooper_columns),
            'event_guests.updated_by:'.implode(',', $trooper_columns),
        ];

        return $with;
    }
}
