<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Queries\GetFaqItems;
use App\Messages\Faq\Resources\FaqItemCollection;
use App\Models\Faq;
use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class ListFaqItemsPageData extends Message
{
    public function __construct(
        private readonly int|null $section_id = null,
    ) {
    }

    public function handle(): array
    {
        return [
            'items' => $this->getItems(),
            'sections' => FaqSection::orderBy(FaqSection::SORT_ORDER)->get(),
            'section_id' => $this->section_id,
            'sortable' => $this->section_id !== null,
        ];
    }

    private function getItems(): FaqItemCollection
    {
        $collection = GetFaqItems::call(section_id: $this->section_id);

        return new FaqItemCollection($collection);
    }
}
