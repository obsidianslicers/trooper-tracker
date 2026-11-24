<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\Costume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A costume the leaderboard's Costume Stats Lookup can find (service-records/Leaderboard).
 *
 * @property Costume $resource
 */
class CostumeOptionResource extends JsonResource
{
    /**
     * @return array{id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            Costume::ID => $this->resource->id,
            Costume::NAME => $this->resource->name,
        ];
    }
}
