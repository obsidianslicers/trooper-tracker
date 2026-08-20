<?php

namespace App\Messages\Account\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperMinorCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (Trooper $minor) => [
                Trooper::ID => $minor->id,
                Trooper::LEGAL_NAME => $minor->legal_name,
                Trooper::DISPLAY_NAME => $minor->display_name,
                Trooper::DATE_OF_BIRTH => $minor->date_of_birth,
            ])->toArray();
    }
}
