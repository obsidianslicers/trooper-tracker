<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One of the leaderboard's Top 5 Deployed Costumes (service-records/Leaderboard), as
 * GetLeaderboardMetrics counts it.
 *
 * @property array{id: int|null, name: string, count: int} $resource
 */
class DeployedCostumeResource extends JsonResource
{
    /**
     * @return array{id: int|null, name: string, count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'count' => (int) $this->resource['count'],
        ];
    }
}
