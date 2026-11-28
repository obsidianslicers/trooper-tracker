<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Corrections;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A completed event on the Request Troop Credit page: enough to recognise it, its shifts,
 * and whether the trooper may ask for credit for it.
 *
 * @mixin Event
 */
class TroopCorrectionEventResource extends JsonResource
{
    public function __construct(Event $event, private readonly Trooper $trooper)
    {
        parent::__construct($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Event::ID => $this->id,
            Event::NAME => $this->name,
            'date' => $this->event_start?->localFormat('D, M j, Y'),
            Event::VENUE => $this->venue,
            'shifts' => $this->event_shifts
                ->map(fn (EventShift $shift): array => [
                    EventShift::ID => $shift->id,
                    'label' => $shift->time_display,
                ])
                ->values()
                ->all(),
            'can_request' => TroopCorrection::canBeRequestedFor($this->resource, $this->trooper),
        ];
    }
}
