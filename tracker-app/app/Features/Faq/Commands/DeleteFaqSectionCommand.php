<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Models\FaqSection;

readonly class DeleteFaqSectionCommand
{
    public function __construct(
        public FaqSection $section,
    ) {}
}
