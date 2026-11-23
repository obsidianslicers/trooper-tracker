<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The totals row of the Event Summary report (admin/reports/EventSummary).
 *
 * @property Collection<int, Event> $resource
 */
class EventSummaryTotalsResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'shifts' => $this->count($this->resource->sum('event_shifts_count')),
            'unique_trooper_count' => $this->count($this->resource->sum('unique_trooper_count')),
            'total_trooper_count' => $this->count($this->resource->sum('total_trooper_count')),
            'direct' => $this->funds($this->resource->sum('charity_direct_funds')),
            'indirect' => $this->funds($this->resource->sum('charity_indirect_funds')),
        ];
    }
}
