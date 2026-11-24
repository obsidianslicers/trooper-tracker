<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\AwardTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An award given lately, on the awards page (service-records/Awards).
 *
 * @mixin AwardTrooper
 */
class RecentAwardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organization = $this->resource->award->organization;

        return [
            AwardTrooper::ID => $this->resource->id,
            'award' => $this->resource->award->name,
            'organization' => $organization->name,
            'logo_url' => map_image_url($organization->image_path_sm, 'img/icons/organization-32x32.png'),
            'trooper_name' => $this->resource->trooper->display_name,
            'trooper_url' => route('service-records.trooper', ['trooper' => $this->resource->trooper]),
            'date' => $this->resource->award_date->localFormat('M j, Y'),
        ];
    }
}
