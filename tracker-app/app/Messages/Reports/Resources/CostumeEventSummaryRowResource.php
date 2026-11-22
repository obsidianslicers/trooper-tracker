<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One costume on the Costume Used Most at Events report.
 */
class CostumeEventSummaryRowResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'events_count' => $this->count($this->resource->events_count),
            'uses_count' => $this->count($this->resource->uses_count),
        ];
    }
}
