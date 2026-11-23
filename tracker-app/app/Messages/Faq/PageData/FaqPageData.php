<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Models\Faq;
use Hyperdrive\Message;

/**
 * The public FAQ page: every section, with its questions grouped under it.
 *
 * @method static array call()
 */
final class FaqPageData extends Message
{
    public function handle(): array
    {
        $items = Faq::query()
            ->orderBy(Faq::SORT_ORDER)
            ->orderBy(Faq::ID)
            ->get()
            ->groupBy(Faq::SECTION_ID);

        return [
            'items' => $items,
            'sections' => GetFaqSections::call(),
        ];
    }
}
