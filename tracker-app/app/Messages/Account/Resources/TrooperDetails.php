<?php

namespace App\Messages\Account\Resources;

use App\Enums\TrooperTheme;
use App\Messages\Account\Queries\GetCostumesWithPrefixes;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Services\TranslationService;
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
            Trooper::LOCALE => $this->locale,
            Trooper::DISPLAY_COSTUME_ID => $this->display_costume_id,
            'display_costumes' => $this->getDisplayCostumes(),
            'theme_enums' => TrooperTheme::toValueLabels(),
            'locale_options' => $this->getLocaleOptions(),
        ];
    }

    /**
     * Empty when the site offers one language, so the page can hide the picker.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function getLocaleOptions(): array
    {
        $translations = app(TranslationService::class);
        $locales = $translations->locales();

        if (count($locales) < 2)
        {
            return [];
        }

        $site_language = $locales[$translations->siteLocale()];

        $options = [[
            'value' => '',
            'label' => __('common.Site default (:language)', ['language' => $site_language]),
        ]];

        foreach ($locales as $locale => $name)
        {
            $options[] = ['value' => $locale, 'label' => $name];
        }

        return $options;
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

                $identifier = ($organization_costume->prefix ?? '').($trooper_organization?->pivot?->identifier ?? '');
                $costume_name = $organization_costume->costume?->name ?? '';
                $organization_name = $organization_costume->organization?->name ?? '';

                $label = "$identifier — $costume_name ($organization_name)";

                return ['value' => $tc->id, 'label' => $label];
            })
            ->toArray();
    }
}
