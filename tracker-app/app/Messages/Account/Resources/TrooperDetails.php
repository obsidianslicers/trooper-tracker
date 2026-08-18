<?php

namespace App\Messages\Account\Resources;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Messages\Account\Queries\GetCostumesWithPrefixes;
use App\Enums\TrooperTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrooperDetails extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::LEGAL_NAME => $this->legal_name,
            Trooper::DISPLAY_NAME => $this->display_name,
            Trooper::PHONE => $this->phone,
            Trooper::THEME => $this->theme,
            Trooper::DISPLAY_COSTUME_ID => $this->display_costume_id,
            'display_costumes' => $this->getDisplayCostumes(),
            'theme_enums' => TrooperTheme::toValueLabels(),
        ];
    }

    private function getDisplayCostumes(): array
    {
        $organizations = $this->organizations()->get();

        $trooper_costumes = GetCostumesWithPrefixes::call(trooper: $this->resource);

        return $trooper_costumes
            ->map(function (TrooperCostume $tc) use ($organizations)
            {
                $organization_costume = $tc->organization_costume;
                $trooper_organization = $organizations->firstWhere('id', $organization_costume->organization_id);

                $identifier = ($organization_costume->prefix ?? '') . ($trooper_organization?->pivot?->identifier ?? '');
                $costume_name = $organization_costume->costume?->name ?? '';
                $organization_name = $organization_costume->organization?->name ?? '';

                $label = "$identifier — $costume_name ($organization_name)";

                return ['value' => $tc->id, 'label' => $label];
            })
            ->toArray();
    }
}