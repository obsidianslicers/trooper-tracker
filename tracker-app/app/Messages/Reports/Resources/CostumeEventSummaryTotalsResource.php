<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The totals row for the costumes on one page of the Costume Event Summary
 * (admin/reports/CostumeEventSummary).
 *
 * @property Collection<int, object> $resource
 */
class CostumeEventSummaryTotalsResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'events_count' => $this->count($this->resource->sum('events_count')),
            'uses_count' => $this->count($this->resource->sum('uses_count')),
        ];
    }
}
