<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Models\AwardTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An award on the service record's Awards tab.
 *
 * @property AwardTrooper $resource
 */
class TrooperAwardResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, date: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            AwardTrooper::ID => $this->resource->id,
            'name' => $this->resource->award->name,
            'date' => $this->resource->award_date?->localFormat('M j, Y'),
        ];
    }
}
