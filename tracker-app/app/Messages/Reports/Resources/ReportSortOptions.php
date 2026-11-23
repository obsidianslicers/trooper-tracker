<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The columns a report can be sorted by, as options for its sort picker
 * (admin/reports/DonationEventSummary).
 *
 * @property array<string, string> $resource column => translation key of its label
 */
class ReportSortOptions extends JsonResource
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toArray(Request $request): array
    {
        return collect($this->resource)
            ->map(fn (string $label, string $column): array => ['value' => $column, 'label' => __($label)])
            ->values()
            ->all();
    }
}
