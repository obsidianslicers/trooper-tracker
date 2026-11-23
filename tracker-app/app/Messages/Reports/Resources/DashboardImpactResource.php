<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Galactic Impact card of the operations dashboard (admin/reports/Display).
 *
 * @property array{total_credits: int|float, volunteer_hours: int|float, internal_donations: int|float} $resource
 */
class DashboardImpactResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_credits' => format_number($this->resource['total_credits'], 2),
            'volunteer_hours' => format_number($this->resource['volunteer_hours']),
            'internal_donations' => format_number($this->resource['internal_donations'], 2),
        ];
    }
}
