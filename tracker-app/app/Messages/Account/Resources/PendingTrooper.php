<?php

namespace App\Messages\Account\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingTrooper extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::LEGAL_NAME => $this->legal_name,
            Trooper::DISPLAY_NAME => $this->display_name,
        ];
    }
}
