<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The filters the Donation Event Summary ran with, to fill its filter form again
 * (admin/reports/DonationEventSummary).
 *
 * @property array{date_start: ?Carbon, date_end: ?Carbon, charity_only: bool, organization_ids: array<int, int>, sort: string, dir: string} $resource
 */
class DonationReportFiltersResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date_start' => $this->resource['date_start']?->format('Y-m-d'),
            'date_end' => $this->resource['date_end']?->format('Y-m-d'),
            'charity_only' => $this->resource['charity_only'],
            'organization_ids' => $this->resource['organization_ids'],
            'sort' => $this->resource['sort'],
            'dir' => $this->resource['dir'],
        ];
    }
}
