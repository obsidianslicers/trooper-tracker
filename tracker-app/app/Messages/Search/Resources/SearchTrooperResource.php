<?php

declare(strict_types=1);

namespace App\Messages\Search\Resources;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A trooper found by the search box, with the club IDs they hold (search/Results).
 *
 * @mixin Trooper
 */
class SearchTrooperResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::ID => $this->resource->id,
            Trooper::DISPLAY_NAME => $this->resource->display_name,
            Trooper::LEGAL_NAME => $this->resource->legal_name ?: null,
            'url' => route('service-records.trooper', ['trooper' => $this->resource]),
            'identifiers' => $this->resource->organizations
                ->filter(fn (Organization $organization): bool => filled($organization->pivot->identifier))
                ->map(fn (Organization $organization): array => [
                    'club' => $organization->name,
                    'label' => ($organization->identifier_display ?? $organization->name)
                        .': '.$organization->pivot->identifier,
                ])
                ->values()
                ->all(),
        ];
    }
}
