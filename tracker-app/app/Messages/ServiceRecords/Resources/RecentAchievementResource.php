<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\TrooperAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A milestone a trooper reached lately, on the achievements page (service-records/Achievements).
 *
 * @mixin TrooperAchievement
 */
class RecentAchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            TrooperAchievement::ID => $this->resource->id,
            'trooper_name' => $this->resource->trooper->display_name,
            'trooper_url' => route('service-records.trooper', ['trooper' => $this->resource->trooper]),
            'description' => $this->resource->display_description,
            'icon' => $this->resource->type->toIcon(),
            'date' => $this->resource->achievement_date->localFormat('M j, Y'),
        ];
    }
}
