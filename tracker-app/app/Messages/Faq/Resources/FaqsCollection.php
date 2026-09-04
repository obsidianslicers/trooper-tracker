<?php

declare(strict_types=1);

namespace App\Messages\Faq\Resources;

use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FaqsCollection extends ResourceCollection
{
    /**
     * Disable the automagic resolution of the resource class for the collection.
     * This prevents Laravel from automatically resolving the resource class for
     * each item in the collection.
     * @return null
     */
    protected function collects(): ?string
    {
        return null;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(FaqSection $section) => [
                FaqSection::ID => $section->id,
                FaqSection::LABEL => $section->label,
                FaqSection::ICON => $section->icon,
                FaqSection::SORT_ORDER => $section->sort_order,
                'faqs' => $this->getQuestions($section),
            ])
            ->toArray();
    }
    private function getQuestions(FaqSection $section): array
    {
        return $section->faqs
            ->map(fn($faq) => [
                Faq::ID => $faq->id,
                Faq::TITLE => $faq->title,
                Faq::SORT_ORDER => $faq->sort_order,
                'has_video' => $faq->has_video,
            ])
            ->toArray();
    }
}
