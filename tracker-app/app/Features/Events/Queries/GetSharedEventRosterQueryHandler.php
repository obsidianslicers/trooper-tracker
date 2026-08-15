<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventGuestStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;

/**
 * Handler for retrieving a shared event roster for external viewing.
 *
 * Returns event data with all shifts and attending troopers for display
 * to external recipients viewing a shared roster link. Only troopers with
 * GOING status are included, sorted by legal name.
 *
 * @implements QueryHandlerInterface<GetSharedEventRosterQuery>
 */
readonly class GetSharedEventRosterQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    /**
     * Execute the query to retrieve a shared event roster for external display.
     *
     * Process:
     * 1. Load event from event_share's event_id
     * 2. Eager load shifts and attending troopers with related data
     * 3. Filter to only GOING status troopers
     * 4. Sort troopers by legal name within each shift
     * 5. Return Event model with relationships loaded
     *
     * @param  GetSharedEventRosterQuery  $message  The query containing the event share
     * @return Event The event with shifts and attending troopers for external viewing
     */
    public function __invoke(object $message): mixed
    {
        $event = Event::with($this->buildRelations())
            ->withShifts()
            ->findOrFail($message->event_share->event_id);

        $event->event_shifts->each(fn ($shift) => $this->transformEventShift($shift));

        $event->event_shifts->each(function ($shift) {
            $shift->event_troopers = $shift->event_troopers->sortBy(fn ($et) => $et->trooper->legal_name)->values();
        });

        return $event;
    }

    private function buildRelations(): array
    {
        $organization_columns = [
            Organization::ID,
            Organization::NAME,
        ];

        $trooper_columns = [
            Trooper::ID,
            Trooper::DISPLAY_NAME,
            Trooper::LEGAL_NAME,
        ];

        $costume_columns = [
            Costume::ID,
            Costume::NAME,
        ];

        return [
            'organization:'.implode(',', $organization_columns),
            'organizations.organization',
            'organizations' => function ($query) {
                $query->orderBy(Organization::NAME);
            },
            'event_shifts' => function ($query) {
                $query->orderBy(EventShift::SHIFT_STARTS_AT, 'asc');
            },
            'event_shifts.event_troopers.trooper:'.implode(',', $trooper_columns),
            'event_shifts.event_troopers.trooper.trooper_costumes.organization_costume',
            'event_shifts.event_troopers.costume:'.implode(',', $costume_columns),
            'event_shifts.event_troopers.costume.organization_costumes',
            'event_shifts.event_troopers' => function ($query) {
                $query->whereIn(EventTrooper::STATUS, EventTrooperStatus::intentToGoArray())
                    ->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
            'event_shifts.event_guests' => function ($query) {
                $query->whereIn(EventGuest::STATUS, EventGuestStatus::intentToGoArray())
                    ->orderBy(EventGuest::SIGNED_UP_AT, 'asc');
            },
        ];
    }
}
