<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The strength and attendance cards of the operations dashboard (admin/reports/Display).
 *
 * @property array<string, int|float> $resource
 */
class DashboardPersonnelResource extends JsonResource
{
    private const KEYS = [
        'active_count',
        'trooper_count',
        'handler_count',
        'new_enlistments',
        'ghost_count',
        'attendance_count',
        'attrition_risk',
    ];

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return collect(self::KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => format_number($this->resource[$key])])
            ->all();
    }
}
