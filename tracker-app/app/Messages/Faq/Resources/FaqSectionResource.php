<?php

declare(strict_types=1);

namespace App\Messages\Faq\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqSectionResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            FaqSection::ID => $this->id,
            FaqSection::LABEL => $this->label,
            FaqSection::ICON => $this->icon,
            'trooper_stamps' => new TrooperStampsResource($this),
        ];
    }
}
