<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\Costume;
use App\Models\EventTrooper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A costume's deployment numbers and its top troopers (service-records/components/CostumeStats),
 * on the leaderboard's costume panel and the costume's own page.
 *
 * @property Costume $resource
 */
class CostumeStatsResource extends JsonResource
{
    /**
     * @param  Collection<int, EventTrooper>  $top_troopers
     * @param  array{total_deployments: int, unique_troopers: int, last_deployed_at: Carbon|null}  $stats
     */
    public function __construct(
        Costume $costume,
        private readonly Collection $top_troopers,
        private readonly array $stats,
    ) {
        parent::__construct($costume);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Costume::ID => $this->resource->id,
            Costume::NAME => $this->resource->name,
            'total_deployments' => format_number($this->stats['total_deployments']),
            'unique_troopers' => format_number($this->stats['unique_troopers']),
            'last_deployed' => $this->stats['last_deployed_at']?->localFormat('M j, Y'),
            'top_troopers' => LeaderboardTrooperResource::collection($this->top_troopers)->resolve($request),
        ];
    }
}
