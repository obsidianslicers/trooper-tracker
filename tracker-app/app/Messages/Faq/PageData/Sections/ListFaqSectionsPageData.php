<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData\Sections;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class ListFaqSectionsPageData extends Message
{
    public function handle(): array
    {
        return [
            'sections' => FaqSection::withCount('faqs')
                ->orderBy(FaqSection::SORT_ORDER)
                ->orderBy(FaqSection::ID)
                ->get(),
        ];
    }
}
