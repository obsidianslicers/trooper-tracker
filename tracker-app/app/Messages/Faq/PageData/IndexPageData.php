<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Queries\GetFaqs;
use App\Messages\Faq\Resources\FaqsCollection;
use Hyperdrive\Message;

/**
 * @method static array call()
 */
final class IndexPageData extends Message
{
    public function handle(): array
    {
        return [
            'sections' => $this->getSections()
        ];
    }

    private function getSections(): FaqsCollection
    {
        $collection = GetFaqs::call();

        return new FaqsCollection($collection);
    }
}
