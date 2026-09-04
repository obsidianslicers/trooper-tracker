<?php

declare(strict_types=1);

namespace App\Messages\Faq\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqItemResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Faq::ID => $this->id,
            Faq::SECTION_ID => $this->section_id,
            Faq::TITLE => $this->title,
            Faq::DESCRIPTION => $this->description,
            Faq::VIDEO_URL => $this->video_url,
            TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($this)
        ];
    }
}
