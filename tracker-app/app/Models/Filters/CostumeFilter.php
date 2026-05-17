<?php

declare(strict_types=1);

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;

class CostumeFilter extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search_term' => 'searchTerm',
        ];
    }

    protected function searchTerm(Builder $query, $value): Builder
    {
        if (strlen($value) >= 3)
        {
            return $query->searchFor($value);
        }

        return $query;
    }
}
