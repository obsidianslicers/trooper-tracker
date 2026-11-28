<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Models\TroopCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A request for troop credit on the service record's Credit Requests tab.
 *
 * @property TroopCorrection $resource
 */
class CreditRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $correction = $this->resource;
        $shift = $correction->event_trooper?->event_shift;

        return [
            TroopCorrection::ID => $correction->id,
            'event_name' => $correction->event->name,
            'troop_date' => ($shift?->shift_starts_at ?? $correction->event->event_start)?->localFormat('M j, Y'),
            'requested_on' => $correction->created_at?->localFormat('M j, Y'),
            TroopCorrection::STATUS => $correction->status->value,
            'decided_on' => $correction->decided_at?->localFormat('M j, Y'),
            'url' => route('events.troop-correction', $correction),
        ];
    }
}
