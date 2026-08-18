<?php

namespace App\Messages\Account\Resources;

use App\Models\Costume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperCostumeCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(Costume $costume) => [
                'costume_id' => $costume->id,
                'name' => $costume->name,
                'costume_organizations' => $costume->costume_organizations ?? '',
            ])->toArray();
    }
}