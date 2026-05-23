<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

readonly class ReorderFaqItemsCommand
{
    public function __construct(
        public array $ordered_ids
    ) {}
}
