<?php

declare(strict_types=1);

namespace App\Models\Scopes;

/**
 * Provides a scope for converting query results into select options.
 */
trait HasToOptionScope
{
    /**
     * Convert query results to an options array for form selects.
     *
     * Maps the specified model attributes to a key-value array suitable for
     * form select options, where the key is the ID and the value is the name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The Eloquent query builder.
     * @param string $name The attribute name to use as the option label.
     * @param string $id The attribute name to use as the option value/key.
     * @return array<int|string, string> Associative array of options.
     */
    public function scopeToOptions($query, string $name, string $id)
    {
        return $query->get()
            ->mapWithKeys(function ($model) use ($name, $id)
            {
                return [$model->{$id} => $model->{$name}];
            })
            ->toArray();
    }
}