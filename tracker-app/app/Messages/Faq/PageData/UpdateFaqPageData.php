<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Resources\FaqSectionOptions;
use App\Models\Faq;
use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class UpdateFaqPageData extends Message
{
    public function __construct(
        public readonly Faq $faq,
    ) {
    }

    public function handle(): array
    {
        return [
            'faq' => $this->faq->load(['created_by', 'updated_by']),
            'sections' => new FaqSectionOptions(FaqSection::orderBy(FaqSection::SORT_ORDER)->get()),
        ];
    }
}
