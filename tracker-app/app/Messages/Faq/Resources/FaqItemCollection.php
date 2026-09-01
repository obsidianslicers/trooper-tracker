<?php

declare(strict_types=1);

namespace App\Messages\Faq\Resources;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FaqItemCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(Faq $item) => [
                Faq::ID => $item->id,
                Faq::TITLE => $item->label,
            ])
            ->toArray();
    }
}
