<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Costume;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;

/**
 * Handler for retrieving event shift display data.
 *
 * Returns an event shift with all related data needed for rendering
 * the shift display, including trooper sign-ups and event details.
 *
 * @implements QueryHandlerInterface<GetEventShiftDisplayQuery>
 */
readonly class GetEventShiftDisplayQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    /**
     * Execute the query to retrieve a single event shift for display.
     *
     * Process:
     * 1. Load event shift with ID from query
     * 2. Eager load event, trooper sign-ups, and related data
     * 3. Return EventShift model with all relationships loaded
     *
     * @param  GetEventShiftDisplayQuery  $message  The query containing the event shift ID
     * @return EventShift The event shift with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        $event_shift = EventShift::with($this->buildRelations())->findOrFail($message->event_shift->id);

        $this->assembleEventShift($event_shift, $message->trooper);

        $this->transformEventShift($event_shift);

        return $event_shift;
    }

    private function buildRelations(): array
    {
        $organization_columns = [
            Organization::ID,
            Organization::NAME,
            Organization::IMAGE_PATH_SM,
            Organization::NODE_PATH,
        ];

        $trooper_columns = [
            Trooper::ID,
            Trooper::LEGAL_NAME,
            Trooper::DISPLAY_NAME,
            Trooper::GUARDIAN_ID,
        ];

        $costume_columns = [
            Costume::ID,
            Costume::NAME,
        ];

        return [
            'event',
            'event.organization:'.implode(',', $organization_columns),
            'event.organizations.organization',
            'event.organizations' => function ($query) {
                $query->orderBy(Organization::NAME);
            },
            'event_shift_stations' => function ($query) {
                $query->withCount('going_event_troopers')
                    ->orderBy(EventShiftStation::SEQUENCE)
                    ->orderBy(EventShiftStation::NAME);
            },
            'event_troopers.trooper:'.implode(',', $trooper_columns),
            'event_troopers.trooper.trooper_costumes.organization_costume',
            'event_troopers.costume:'.implode(',', $costume_columns),
            'event_troopers.costume.organization_costumes',
            'event_troopers.backup_costume:'.implode(',', $costume_columns),
            'event_troopers.backup_costume.organization_costumes',
            'event_troopers.event_shift_station',
            'event_troopers.added_by_trooper:'.implode(',', $trooper_columns),
            'event_troopers.updated_by:'.implode(',', $trooper_columns),
            'event_troopers' => function ($query) {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
            'event_guests.added_by_trooper:'.implode(',', $trooper_columns),
            'event_guests.updated_by:'.implode(',', $trooper_columns),
            'event_guests' => function ($query) {
                $query->orderBy(EventGuest::NAME, 'asc');
            },
        ];
    }
}
