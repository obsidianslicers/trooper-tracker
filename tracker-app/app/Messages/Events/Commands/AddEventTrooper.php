<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\AddEventTrooperOutcome;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command staff add a trooper to a shift's roster from the event's admin page.
 *
 * A costume that isn't the trooper's, or a club they can't credit on this event, is dropped
 * rather than refused. A shift with stations needs one of its stations.
 *
 * @method static AddEventTrooperOutcome call(EventShift $event_shift, Trooper $trooper, Trooper $added_by_trooper, ?int $costume_id = null, ?int $organization_id = null, ?int $event_shift_station_id = null)
 */
final class AddEventTrooper extends Message
{
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly Trooper $trooper,
        private readonly Trooper $added_by_trooper,
        private readonly ?int $costume_id = null,
        private readonly ?int $organization_id = null,
        private readonly ?int $event_shift_station_id = null,
    ) {}

    public function handle(): AddEventTrooperOutcome
    {
        $this->event_shift->loadMissing('event_shift_stations');

        if ($this->event_shift->isSignedUp($this->trooper))
        {
            return AddEventTrooperOutcome::ALREADY_SIGNED_UP;
        }

        if (!$this->event_shift->isValidStationChoice($this->event_shift_station_id))
        {
            return AddEventTrooperOutcome::STATION_REQUIRED;
        }

        CreateEventTrooper::call(
            event_shift: $this->event_shift,
            trooper: $this->trooper,
            added_by_trooper: $this->added_by_trooper,
            organization_id: $this->eligibleOrganizationId(),
            costume_id: $this->ownCostumeId(),
            event_shift_station_id: $this->event_shift_station_id,
        );

        return AddEventTrooperOutcome::ADDED;
    }

    private function ownCostumeId(): ?int
    {
        if ($this->costume_id === null)
        {
            return null;
        }

        $is_own = Costume::forTrooper($this->trooper->id)->where('id', $this->costume_id)->exists();

        return $is_own ? $this->costume_id : null;
    }

    private function eligibleOrganizationId(): ?int
    {
        if ($this->organization_id === null)
        {
            return null;
        }

        $eligible_ids = $this->trooper->eligibleOrgsForEvent($this->event_shift->event)->pluck('id')->all();

        return in_array($this->organization_id, $eligible_ids, true) ? $this->organization_id : null;
    }
}
