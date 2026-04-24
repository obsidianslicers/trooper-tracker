<?php

declare(strict_types=1);

namespace App\Features\Search\Queries;

use App\Enums\SearchType;

readonly class GlobalSearchQuery
{
    public function __construct(
        public readonly string $term,
        public readonly string $type = SearchType::ALL->value,
    ) {}
}
