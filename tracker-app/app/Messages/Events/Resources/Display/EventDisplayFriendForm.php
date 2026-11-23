<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The choices for signing a friend up to a shift from the event page (Add a friend): their
 * organization when the event limits clubs, the station, and their costume.
 *
 * @mixin Trooper
 */
class EventDisplayFriendForm extends JsonResource
{
    public function __construct(Trooper $trooper, private readonly EventShift $event_shift)
    {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event_shift->event;
        $requires_costume = $event->requiresCostume();
        $costume_options = $requires_costume ? $this->event_shift->costumeOptionsFor($this->resource) : [];
        $costume_default = $requires_costume
            ? $this->event_shift->defaultCostumeIdFor($this->resource, $costume_options) : null;

        return [
            'trooper' => [Trooper::ID => $this->id, 'name' => $this->display_name],
            'organizations' => $this->organizationOptions($event),
            'stations' => $this->event_shift->event_shift_stations
                ->sortBy([[EventShiftStation::SEQUENCE, 'asc'], [EventShiftStation::NAME, 'asc']])
                ->map(fn (EventShiftStation $s) => ['value' => $s->id, 'label' => $s->name])
                ->values(),
            'requires_costume' => $requires_costume,
            'costumes' => collect($costume_options)
                ->map(fn (string $name, int $id) => ['value' => (string) $id, 'label' => $name])
                ->values(),
            'default_costume' => $requires_costume
                ? ($costume_default !== null ? (string) $costume_default : '') : 'none',
        ];
    }

    /**
     * Their organization only matters when an organization on the event has its own limits.
     *
     * @return Collection<int, array{value: int, label: string}>
     */
    private function organizationOptions(Event $event): Collection
    {
        $event_has_org_limits = $event->organizations
            ->contains(fn ($o) => $o->pivot->can_attend
                && ($o->pivot->troopers_allowed !== null || $o->pivot->handlers_allowed !== null));

        return ($event_has_org_limits ? $this->resource->eligibleOrgsForEvent($event) : collect())
            ->map(fn (Organization $o) => ['value' => $o->id, 'label' => $o->name])
            ->values();
    }
}
