<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Messages\Faq\Resources\FaqSectionResource;
use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(FaqSection $section)
 */
final class UpdateSectionPageData extends Message
{
    public function __construct(
        private readonly FaqSection $section,
    ) {
    }

    public function handle(): array
    {
        return [
            'section' => $this->getFaqSection(),
        ];
    }

    private function getFaqSection(): FaqSectionResource
    {
        return new FaqSectionResource($this->section);
    }
}
