<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Messages\Faq\Resources\FaqSectionCollection;
use Hyperdrive\Message;

/**
 * @method static array call()
 */
final class ListFaqSectionsPageData extends Message
{
    public function handle(): array
    {
        return [
            'sections' => $this->getSections()
        ];
    }

    private function getSections(): FaqSectionCollection
    {
        $collection = GetFaqSections::call();

        return new FaqSectionCollection($collection);
    }
}
