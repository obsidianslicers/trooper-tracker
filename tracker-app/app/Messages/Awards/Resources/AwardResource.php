<?php

declare(strict_types=1);

namespace App\Messages\Awards\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An award on its create, update, troopers and assign pages.
 *
 * @mixin Award
 */
class AwardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Award::ID => $this->id,
            Award::ORGANIZATION_ID => $this->organization_id,
            'organization_name' => $this->organization?->name,
            Award::NAME => $this->name ?? '',
            Award::FREQUENCY => $this->frequency->value,
            'frequency_label' => $this->frequency->label(),
            TrooperStampsResource::TROOPER_STAMPS => $this->when(
                $this->exists,
                fn () => new TrooperStampsResource($this->resource)
            ),
        ];
    }
}
