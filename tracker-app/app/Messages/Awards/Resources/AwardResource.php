<?php

declare(strict_types=1);

namespace App\Messages\Awards\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The award on the Command Staff award update page: what it is, and who made and changed it.
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
            ...(new AwardSummaryResource($this->resource))->toArray($request),
            TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($this->resource),
        ];
    }
}
