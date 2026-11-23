<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The choices for adding a trooper to a shift: the club they troop for when that matters, the
 * shift's stations, and their costumes.
 *
 * @method static array<string, mixed> call(EventShift $event_shift, ?int $trooper_id = null)
 */
final class AddTrooperFormPageData extends Message
{
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly ?int $trooper_id = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $trooper = Trooper::active()->findOrFail((int) $this->trooper_id);

        $event = $this->event_shift->event;
        $event->loadMissing('event_organizations');
        $this->event_shift->loadMissing('event_shift_stations');

        $requires_costume = $event->requiresCostume();
        $costume_options = $requires_costume ? $this->event_shift->costumeOptionsFor($trooper) : [];
        $costume_default = $requires_costume
            ? $this->event_shift->defaultCostumeIdFor($trooper, $costume_options) : null;

        return [
            'trooper' => ['id' => $trooper->id, 'name' => $trooper->display_name],
            'organizations' => $this->organizationOptions($trooper, $event),
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
    private function organizationOptions(Trooper $trooper, Event $event): Collection
    {
        $event_has_org_limits = $event->organizations
            ->contains(fn ($o) => $o->pivot->can_attend
                && ($o->pivot->troopers_allowed !== null || $o->pivot->handlers_allowed !== null));

        return ($event_has_org_limits ? $trooper->eligibleOrgsForEvent($event) : collect())
            ->map(fn (Organization $o) => ['value' => $o->id, 'label' => $o->name])
            ->values();
    }
}
