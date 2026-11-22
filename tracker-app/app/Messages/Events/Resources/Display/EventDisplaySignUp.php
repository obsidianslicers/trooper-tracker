<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventStatus;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The viewer's own Sign Up for a shift, at the end of its roster: what there is to choose
 * (club, station, costume preselected when we can guess it, Going/Tentative) and the hint
 * beside the button. Null once they're signed up or can't sign up.
 *
 * @mixin EventShift
 */
class EventDisplaySignUp extends JsonResource
{
    public function __construct(EventShift $event_shift, private readonly EventDisplayContext $context)
    {
        parent::__construct($event_shift);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toArray(Request $request): ?array
    {
        $viewer = $this->context->viewer;

        if ($this->signUpFor($viewer) !== null)
        {
            return null;
        }

        if (!$this->context->has_brief_ack)
        {
            return ['needs_mission_brief' => true];
        }

        if (!$this->canSignUp($viewer))
        {
            return null;
        }

        $event = $this->context->event;
        $is_manual = $event->status === EventStatus::MANUAL_SELECTION;
        $spots_left = $this->trooperSpotsLeft();
        $is_full = !$is_manual && $spots_left === 0;
        $costumes = $event->requiresCostume() ? $this->costumeOptionsFor($viewer) : [];
        $default_costume = $event->requiresCostume()
            ? $this->defaultCostumeIdFor($viewer, $costumes) : null;

        return [
            'needs_mission_brief' => false,
            'url' => route('events.signup', ['event_shift' => $this->resource]),
            'is_full' => $is_full,
            'hint' => $this->hint($is_manual, $is_full, $spots_left),
            'organizations' => $this->organizationOptions(),
            'stations' => $this->stationOptions(),
            'requires_costume' => $event->requiresCostume(),
            'costumes' => collect($costumes)
                ->map(fn (string $name, int $id) => ['value' => (string) $id, 'label' => $name])
                ->values()
                ->all(),
            'default_costume' => $event->requiresCostume()
                ? ($default_costume !== null ? (string) $default_costume : '') : 'none',
            'plans' => collect($event->planStatusOptions())
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    private function hint(bool $is_manual, bool $is_full, ?int $spots_left): ?string
    {
        return match (true)
        {
            $is_manual => __('events.Command staff picks who goes from the sign-ups.'),
            $is_full => __("events.Full. You'd be #:place on stand-by, and move up in order if someone going cancels.", [
                'place' => $this->standByCount() + 1,
            ]),
            $spots_left !== null => trans_choice('events.:count spot left|:count spots left', $spots_left),
            default => null,
        };
    }

    /**
     * The clubs they could troop as, when one of them has its own limits on this event.
     *
     * @return list<array{value: int, label: string}>
     */
    private function organizationOptions(): array
    {
        $event = $this->context->event;
        $eligible = $this->context->viewer->eligibleOrgsForEvent($event);
        $limited_ids = $event->event_organizations
            ->filter(fn ($o) => $o->troopers_allowed !== null || $o->handlers_allowed !== null)
            ->pluck('organization_id')
            ->all();

        if ($eligible->whereIn('id', $limited_ids)->isEmpty())
        {
            return [];
        }

        return $eligible
            ->map(fn (Organization $o) => ['value' => $o->id, 'label' => $o->name])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function stationOptions(): array
    {
        if (!$this->usesStations())
        {
            return [];
        }

        return $this->event_shift_stations
            ->sortBy([[EventShiftStation::SEQUENCE, 'asc'], [EventShiftStation::NAME, 'asc']])
            ->map(fn (EventShiftStation $s) => ['value' => $s->id, 'label' => $s->name])
            ->values()
            ->all();
    }
}
