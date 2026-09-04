<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Messages\Faq\Resources\FaqSectionOptions;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class CreateItemPageData extends Message
{
    public function __construct(
        private readonly int|null $section_id = null,
    ) {
    }

    public function handle(): array
    {
        return [
            'section_id' => $this->section_id,
            'section_options' => $this->getSectionOptions()
        ];
    }

    private function getSectionOptions(): FaqSectionOptions
    {
        $collection = GetFaqSections::call();

        return new FaqSectionOptions($collection);
    }
}
