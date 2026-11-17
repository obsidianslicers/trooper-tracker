<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The costume, club and station choices for adding a trooper to a shift's roster.
 *
 * @property Trooper $resource
 */
class AddEventTrooperChoices extends JsonResource
{
    public function __construct(
        Trooper $trooper,
        private readonly Event $event,
        private readonly EventShift $event_shift,
    ) {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trooper = $this->resource;

        return [
            'trooper_id' => $trooper->id,
            'display_name' => $trooper->display_name,
            'costume_options' => Costume::forTrooper($trooper->id)
                ->pluck(Costume::NAME, Costume::ID)
                ->map(fn (string $name, int $id): array => ['value' => $id, 'label' => $name])
                ->values()
                ->all(),
            'organization_options' => $trooper->eligibleOrgsForEvent($this->event)
                ->map(fn (Organization $org): array => ['value' => $org->id, 'label' => $org->name])
                ->values()
                ->all(),
            'station_options' => $this->event_shift->event_shift_stations
                ->map(fn (EventShiftStation $s): array => ['value' => $s->id, 'label' => $s->name])
                ->values()
                ->all(),
        ];
    }
}
