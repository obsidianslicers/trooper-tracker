<?php

declare(strict_types=1);

namespace App\Messages\Awards\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A trooper on the Command Staff "troopers awarded" list, with the grant it came from.
 *
 * @mixin Trooper
 */
class AwardedTrooperResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'award_trooper_id' => $this->pivot->id,
            Trooper::DISPLAY_NAME => $this->display_name,
            'award_date' => $this->pivot->award_date->localFormat('M d, Y'),
        ];
    }
}
