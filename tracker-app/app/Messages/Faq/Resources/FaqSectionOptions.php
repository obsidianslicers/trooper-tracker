<?php

declare(strict_types=1);

namespace App\Messages\Faq\Resources;

use App\Models\FaqSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FaqSectionOptions extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (FaqSection $section) => ['value' => $section->id, 'label' => $section->label])
            ->all();
    }
}
