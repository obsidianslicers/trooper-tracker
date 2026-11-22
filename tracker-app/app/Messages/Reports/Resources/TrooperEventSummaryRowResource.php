<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One trooper on the Trooper Event Summary report.
 *
 * @property Trooper $resource
 */
class TrooperEventSummaryRowResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'display_name' => $this->resource->display_name,
            'profile_url' => route('admin.troopers.show', ['trooper' => $this->resource]),
            'events_count' => $this->count($this->resource->events_count),
            'event_shifts_count' => $this->count($this->resource->event_shifts_count),
        ];
    }
}
