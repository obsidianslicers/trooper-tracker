<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class LowerCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model value.
     *
     * Returns the value as-is without modification.
     *
     * @param Model $model The model instance.
     * @param string $key The attribute key.
     * @param mixed $value The raw value from the database.
     * @param array $attributes All model attributes.
     * @return string The unchanged value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value;
    }

    /**
     * Transform the attribute to its underlying model value.
     *
     * Converts the value to lowercase before storing in the database.
     *
     * @param Model $model The model instance.
     * @param string $key The attribute key.
     * @param mixed $value The value to be stored.
     * @param array $attributes All model attributes.
     * @return string The lowercased value.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return strtolower($value);
    }
}
