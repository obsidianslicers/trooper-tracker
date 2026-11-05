<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries;

use App\Messages\Concerns\HasEventDisplayAssembler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Query to retrieve a single event for display with all related data.
 *
 * Returns the specified event with eagerly loaded relationships including
 * organizations, shifts, trooper sign-ups, and other relevant details.
 *
 * @method static Event call(Event $event, Trooper $trooper)
 */
final class GetEventDisplay extends Message
{
    use HasEventDisplayAssembler;

    /**
     * Create a new query instance.
     *
     * @param  Event  $event  The event to retrieve for display
     * @param  Trooper  $trooper  The trooper viewing the event
     */
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
    ) {}

    /**
     * Execute the query to retrieve a single event for display.
     *
     * Process:
     * 1. Load event with ID from query
     * 2. Eager load shifts, trooper sign-ups, and related data
     * 3. Return Event model with all relationships loaded
     *
     * @return Event The event with all related data for display
     */
    public function handle(): Event
    {
        $this->bootHasEventDisplayAssembler();

        $event = Event::with($this->buildRelations())
            ->withShifts()
            ->findOrFail($this->event->id);

        $this->assembleEvent($event, $this->trooper);

        $this->assembleEventOrganization($event);

        $event->event_shifts->each(fn ($shift) => $this->transformEventShift($shift));

        return $event;
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
            'organization:'.implode(',', $organization_columns),
            'organizations.organization',
            'organizations' => function ($query)
            {
                $query->orderBy(Organization::NAME);
            },
            'event_shifts' => function ($query)
            {
                $query->orderBy(EventShift::SHIFT_STARTS_AT, 'asc');
            },
            'event_shifts.event_shift_stations' => function ($query)
            {
                $query->withCount('going_event_troopers')
                    ->orderBy(EventShiftStation::SEQUENCE)
                    ->orderBy(EventShiftStation::NAME);
            },
            'event_shifts.event_guests.added_by_trooper:'.implode(',', $trooper_columns),
            'event_shifts.event_guests.updated_by:'.implode(',', $trooper_columns),
            'event_shifts.event_guests' => function ($query)
            {
                $query->orderBy(EventGuest::NAME);
            },
            'event_shifts.event_troopers.trooper' => function ($query) use ($trooper_columns)
            {
                $query->withTrashed()->select($trooper_columns);
            },
            'event_shifts.event_troopers.trooper.trooper_costumes.organization_costume',
            'event_shifts.event_troopers.added_by_trooper:'.implode(',', $trooper_columns),
            'event_shifts.event_troopers.updated_by:'.implode(',', $trooper_columns),
            'event_shifts.event_troopers.costume:'.implode(',', $costume_columns),
            'event_shifts.event_troopers.costume.organization_costumes',
            'event_shifts.event_troopers.backup_costume:'.implode(',', $costume_columns),
            'event_shifts.event_troopers.backup_costume.organization_costumes',
            'event_shifts.event_troopers.event_shift_station',
            'event_shifts.event_troopers' => function ($query)
            {
                $query->orderBy(EventTrooper::SIGNED_UP_AT, 'asc');
            },
            'event_uploads',
            'event_uploads.troopers',
        ];
    }
}
