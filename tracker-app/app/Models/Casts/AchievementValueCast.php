<?php

declare(strict_types=1);

namespace App\Models\Casts;

use App\Enums\AchievementType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Eloquent cast for trooper achievement values.
 *
 * Converts achievement values between database storage and PHP types based on the
 * achievement type. Number-based achievements (rank, event count, funds) are cast
 * to numeric types, while milestone achievements are cast to booleans.
 */
class AchievementValueCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * Converts database values to appropriate PHP types based on the achievement type:
     * - 'number' types: converted to int or float
     * - 'bool' types: converted to boolean
     * - default: returned as-is
     *
     * @param  Model  $model  The model instance
     * @param  string  $key  The attribute key being cast
     * @param  mixed  $value  The raw database value
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return int|float|bool|mixed|null The cast value
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $type = AchievementType::from($attributes['type']);

        return match ($type->valueType())
        {
            'number' => is_numeric($value) ? $value + 0 : null,
            'bool' => (bool) $value,
            default => $value,
        };
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * Converts PHP values to database-storable strings based on the achievement type:
     * - 'number' types: converted to string representation
     * - 'bool' types: converted to '1' or '0'
     * - default: returned as-is
     *
     * @param  Model  $model  The model instance
     * @param  string  $key  The attribute key being cast
     * @param  mixed  $value  The value to be stored
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return string The database-ready value
     */
    public function set($model, string $key, $value, array $attributes)
    {
        // If type hasn't been set yet, return value as-is (string)
        // This handles the case during model creation where VALUE might be set before TYPE
        if (!isset($attributes['type']))
        {
            return (string) $value;
        }

        $type = AchievementType::from($attributes['type']);

        return match ($type->valueType())
        {
            'number' => (string) $value,
            'bool' => $value ? '1' : '0',
            default => $value,
        };
    }
}
