<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrooperStampsResource extends JsonResource
{
    public const string TROOPER_STAMPS = 'trooper_stamps';

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'created_id' => $this->created_by?->id,
            'created_by' => $this->created_by?->legal_name,
            'created_at' => $this->created_at?->diffForHumans(),
            'updated_id' => $this->updated_by?->id,
            'updated_by' => $this->updated_by?->legal_name,
            'updated_at' => $this->updated_at?->diffForHumans(),
            'deleted_id' => $this->deleted_by?->id,
            'deleted_by' => $this->deleted_by?->legal_name,
            'deleted_at' => $this->deleted_at?->diffForHumans(),
        ];
    }
}
