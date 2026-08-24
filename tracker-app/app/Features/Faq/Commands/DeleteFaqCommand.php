<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Models\Faq;

readonly class DeleteFaqCommand
{
    public function __construct(
        public Faq $faq,
    ) {}
}
