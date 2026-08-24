<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

readonly class CreateFaqSectionCommand
{
    public function __construct(
        public string $label,
        public string $icon,
    ) {}
}
