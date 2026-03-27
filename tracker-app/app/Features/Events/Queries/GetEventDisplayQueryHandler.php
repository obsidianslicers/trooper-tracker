<?php

declare(strict_types=1);

namespace App\Features\Events\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;

/**
 * Handler for retrieving a single event for display.
 *
 * Returns event data with all shifts, trooper sign-ups, and related information
 * needed for rendering the event display page.
 *
 * @implements QueryHandlerInterface<GetEventDisplayQuery>
 */
readonly class GetEventDisplayQueryHandler implements QueryHandlerInterface
{
    use HasEventDisplayAssembler;

    public function __construct()
    {
        $this->bootHasEventDisplayAssembler();
    }

    /**
     * Execute the query to retrieve a single event for display.
     *
     * Process:
     * 1. Load event with ID from query
     * 2. Eager load shifts, trooper sign-ups, and related data
     * 3. Return Event model with all relationships loaded
     *
     * @param  GetEventDisplayQuery  $message  The query containing the event ID
     * @return Event The event with all related data for display
     */
    public function __invoke(object $message): mixed
    {
        $event = Event::with($this->buildRelations())
            ->withShifts()
            ->findOrFail($message->event->id);

        $this->assembleEvent($event, $message->trooper);

        $this->assembleEventOrganization($event);

        $event->event_shifts->each(fn($shift) => $this->transformEventShift($shift));

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
            Trooper::GUARDIAN_ID,
        ];

        $costume_columns = [
            Costume::ID,
            Costume::NAME,
        ];

        return [
            'organization:' . implode(',', $organization_columns),
            'organizations.organization',
            'organizations' => function ($query)
            {
                $query->orderBy(Organization::NAME);
            },
            'event_shifts' => function ($query)
            {
                $query->orderBy(EventShift::SHIFT_STARTS_AT, 'asc');
            },
            'event_shifts.event_guests.added_by_trooper:' . implode(',', $trooper_columns),
            'event_shifts.event_guests' => function ($query)
            {
                $query->orderBy(EventGuest::NAME);
            },
            'event_shifts.event_troopers.trooper:' . implode(',', $trooper_columns),
            'event_shifts.event_troopers.trooper.trooper_costumes.organization_costume',
            'event_shifts.event_troopers.added_by_trooper:' . implode(',', $trooper_columns),
            'event_shifts.event_troopers.costume:' . implode(',', $costume_columns),
            'event_shifts.event_troopers.backup_costume:' . implode(',', $costume_columns),
            'event_shifts.event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
        ];
    }
}
