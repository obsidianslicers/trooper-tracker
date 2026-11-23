<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The filters a club report ran with, to fill its filter form again
 * (admin/reports/CostumeEventSummary and TrooperEventSummary).
 *
 * @property array{organization: ?Organization, date_start: ?Carbon, date_end: ?Carbon, active_only?: bool} $resource
 */
class ClubReportFiltersResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'organization_id' => $this->resource['organization']?->id,
            'date_start' => $this->resource['date_start']?->format('Y-m-d'),
            'date_end' => $this->resource['date_end']?->format('Y-m-d'),
            'active_only' => $this->when(
                array_key_exists('active_only', $this->resource),
                fn (): bool => $this->resource['active_only']
            ),
        ];
    }
}
