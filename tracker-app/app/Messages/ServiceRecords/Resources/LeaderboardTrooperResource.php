<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One ranked trooper on the leaderboards (service-records/Leaderboard and Costume): a grouped
 * EventTrooper row with its troop_count and trooper.
 *
 * @property EventTrooper&object{troop_count: int} $resource
 */
class LeaderboardTrooperResource extends JsonResource
{
    /**
     * @return array{id: int, display_name: string, legal_name: string|null, url: string, troop_count: int}
     */
    public function toArray(Request $request): array
    {
        $trooper = $this->resource->trooper;

        return [
            Trooper::ID => $trooper->id,
            Trooper::DISPLAY_NAME => $trooper->display_name,
            //  only when it says something more than the username
            Trooper::LEGAL_NAME => filled($trooper->legal_name) && $trooper->legal_name !== $trooper->display_name
                ? $trooper->legal_name
                : null,
            'url' => route('service-records.trooper', ['trooper' => $trooper]),
            'troop_count' => (int) $this->resource->troop_count,
        ];
    }
}
