<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData\Sections;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class UpdateFaqSectionPageData extends Message
{
    public function __construct(
        public readonly FaqSection $section,
    ) {}

    public function handle(): array
    {
        return [
            'section' => $this->section->load(['created_by', 'updated_by']),
        ];
    }
}
