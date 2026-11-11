<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a dollar amount to a float rounded to cents, with null stored as zero.
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * @param  Model  $model  The model instance.
     * @param  string  $key  The attribute key.
     * @param  mixed  $value  The raw value from the database.
     * @param  array<string, mixed>  $attributes  All model attributes.
     * @return float The amount in dollars, rounded to cents.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        return round((float) $value, 2);
    }

    /**
     * @param  Model  $model  The model instance.
     * @param  string  $key  The attribute key.
     * @param  mixed  $value  The value to be stored.
     * @param  array<string, mixed>  $attributes  All model attributes.
     * @return float The amount rounded to cents, or zero if null.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): float
    {
        return round((float) $value, 2);
    }
}
