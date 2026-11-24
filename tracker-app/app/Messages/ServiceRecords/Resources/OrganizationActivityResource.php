<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A club and how many events it held, for the leaderboard's Organization Activity bars
 * (service-records/Leaderboard).
 *
 * @property Organization&object{events_count: int} $resource
 */
class OrganizationActivityResource extends JsonResource
{
    /**
     * @return array{name: string, count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::NAME => $this->resource->name,
            'count' => (int) $this->resource->events_count,
        ];
    }
}
