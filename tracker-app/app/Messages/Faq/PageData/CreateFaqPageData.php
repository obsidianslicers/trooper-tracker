<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Resources\FaqSectionOptions;
use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class CreateFaqPageData extends Message
{
    public function __construct(
        public readonly ?int $section_id,
    ) {}

    public function handle(): array
    {
        return [
            'section_id' => $this->section_id,
            'sections' => new FaqSectionOptions(FaqSection::orderBy(FaqSection::SORT_ORDER)->get()),
        ];
    }
}
