<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The trooper's answers so far on the first-time setup form, and its choices (account/Setup).
 *
 * @mixin Trooper
 */
class SetupTrooperResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::EMAIL => $this->resource->email,
            Trooper::LEGAL_NAME => $this->resource->legal_name,
            Trooper::THEME => $this->resource->theme->value,
            Trooper::NOTIFICATION_FREQUENCY => $this->resource->notification_frequency->value,
            'theme_options' => TrooperTheme::toValueLabels(),
            'frequency_options' => NotificationFrequency::toValueLabels(),
        ];
    }
}
