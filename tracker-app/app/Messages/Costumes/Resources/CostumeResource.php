<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Costume;
use App\Models\OrganizationCostume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The costume on the Command Staff costume update page: its name, the clubs that offer it, and
 * whether it can be deleted (the handler costume can't).
 *
 * @mixin Costume
 */
class CostumeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Costume::ID => $this->id,
            Costume::NAME => $this->name,
            'organization_ids' => OrganizationCostume::query()
                ->where(OrganizationCostume::COSTUME_ID, $this->id)
                ->pluck(OrganizationCostume::ORGANIZATION_ID),
            'can_delete' => !$this->countsAsHandler(),
            TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($this->resource),
        ];
    }
}
