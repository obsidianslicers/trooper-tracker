<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Models\FaqSection;

readonly class UpdateFaqSectionCommand
{
    public function __construct(
        public FaqSection $section,
        public string $label,
        public string $icon,
    ) {}
}
