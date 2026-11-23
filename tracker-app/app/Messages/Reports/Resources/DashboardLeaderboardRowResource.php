<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One club on the operations dashboard's leaderboard (admin/reports/Display). "-" means none.
 *
 * @property array{name: string, events_completed: int|float, troopers_attended: int|float, direct_funds_raised: int|float, indirect_funds_raised: int|float, total_funds_raised: int|float} $resource
 */
class DashboardLeaderboardRowResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'events_completed' => $this->dash($this->resource['events_completed']),
            'troopers_attended' => $this->dash($this->resource['troopers_attended']),
            'direct_funds_raised' => $this->dash($this->resource['direct_funds_raised'], 2),
            'indirect_funds_raised' => $this->dash($this->resource['indirect_funds_raised'], 2),
            'total_funds_raised' => $this->dash($this->resource['total_funds_raised'], 2),
        ];
    }

    private function dash(int|float|string|null $value, int $decimals = 0): string
    {
        return (float) $value == 0.0 ? '-' : format_number($value, $decimals);
    }
}
