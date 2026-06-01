<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts integer attributes to zero when null.
 */
final class EnforceZeroCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model value.
     *
     * Returns the value as-is without modification.
     *
     * @param Model $model The model instance.
     * @param string $key The attribute key.
     * @param mixed $value The raw value from the database.
     * @param array<string, mixed> $attributes All model attributes.
     * @return int The unchanged value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value ?? 0;
    }

    /**
     * Transform the attribute to its underlying model value.
     *
     * Converts the value to zero if null before storing in the database.
     *
     * @param Model $model The model instance.
     * @param string $key The attribute key.
     * @param mixed $value The value to be stored.
     * @param array<string, mixed> $attributes All model attributes.
     * @return int The value or zero if null.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value ?? 0;
    }
}
