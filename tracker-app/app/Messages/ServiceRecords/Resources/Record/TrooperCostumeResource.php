<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A costume on the service record's Costumes tab: its name, pictures and clubs.
 *
 * @property object{id: int, name: string, image_urls: mixed, costume_organizations: string} $resource
 */
class TrooperCostumeResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, image_urls: list<string>, organizations: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'image_urls' => collect($this->resource->image_urls ?? [])->values()->all(),
            'organizations' => (string) $this->resource->costume_organizations,
        ];
    }
}
