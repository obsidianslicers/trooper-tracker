<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The totals row of the Event Type Counts report (admin/reports/EventTypeCount).
 *
 * @property Collection<int, object> $resource
 */
class EventTypeCountTotalsResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'count' => $this->count($this->resource->sum('count')),
            'unique_trooper_count' => $this->count($this->resource->sum('unique_trooper_count')),
            'total_trooper_count' => $this->count($this->resource->sum('total_trooper_count')),
        ];
    }
}
