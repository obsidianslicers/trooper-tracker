<?php

namespace App\Messages\Account\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperFriendCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(Trooper $friend) => [
                Trooper::ID => $friend->id,
                Trooper::LEGAL_NAME => $friend->legal_name,
                Trooper::DISPLAY_NAME => $friend->display_name,
            ])->toArray();
    }
}