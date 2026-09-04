<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Messages\Faq\Resources\FaqItemResource;
use App\Messages\Faq\Resources\FaqSectionOptions;
use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static array call(Faq $item)
 */
final class UpdateItemPageData extends Message
{
    public function __construct(
        private readonly Faq $item,
    ) {
    }

    public function handle(): array
    {
        return [
            'item' => $this->getFaqItem(),
            'section_options' => $this->getSectionOptions()
        ];
    }

    private function getFaqItem(): FaqItemResource
    {
        return new FaqItemResource($this->item);
    }

    private function getSectionOptions(): FaqSectionOptions
    {
        $collection = GetFaqSections::call();

        return new FaqSectionOptions($collection);
    }
}
