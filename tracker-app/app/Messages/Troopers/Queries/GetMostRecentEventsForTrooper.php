<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query for retrieving the most recent attended events for a trooper.
 *
 * Returns organizations with the most recent event shift where the trooper
 * attended (status = ATTENDED), grouped by organization.
 *
 * @method static Collection<int, Organization> call(Trooper $trooper)
 */
final class GetMostRecentEventsForTrooper extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose recent events to retrieve
     */
    public function __construct(private readonly Trooper $trooper) {}

    /**
     * Execute the query to retrieve most recent events.
     *
     * Process:
     * 1. Load all organizations of type 'organization'
     * 2. For each organization, find the most recent event shift where trooper attended
     * 3. Set 'event_shift' property on each organization (null if no matches)
     * 4. Return collection of organizations with event_shift data
     *
     * @return Collection<int, Organization> Organizations with event_shift property
     */
    public function handle(): Collection
    {
        $organizations = Organization::ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get();

        foreach ($organizations as $organization)
        {
            $event_filter = function ($q) use ($organization)
            {
                $q->where(Event::PRIMARY_ORGANIZATION_ID, $organization->id);
            };

            $trooper_filter = function ($q)
            {
                $q->where(EventTrooper::TROOPER_ID, $this->trooper->id)
                    ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
            };

            $event_shift = EventShift::with('event')
                ->whereHas('event', $event_filter)
                ->whereHas('event_troopers', $trooper_filter)
                ->orderByDesc(EventShift::SHIFT_STARTS_AT)
                ->first();

            $organization->event_shift = null;

            if ($event_shift)
            {
                $organization->event_shift = $event_shift;
            }
        }

        return $organizations;
    }
}
