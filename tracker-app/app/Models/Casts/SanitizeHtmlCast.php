<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Sanitizes HTML from string attributes when storing.
 */
class SanitizeHtmlCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model value.
     *
     * Returns the value as-is without modification (sanitization occurs on set).
     *
     * @param mixed $model The model instance.
     * @param string $key The attribute key.
     * @param mixed $value The raw value from the database.
     * @param array<string, mixed> $attributes All model attributes.
     * @return string|null The unchanged value.
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null)
        {
            return null;
        }

        return $value;
    }

    /**
     * Transform the attribute to its underlying model value.
     *
     * Decodes HTML entities and strips all HTML tags before storing in the database.
     *
     * @param mixed $model The model instance.
     * @param string $key The attribute key.
     * @param mixed $value The value to be stored.
     * @param array<string, mixed> $attributes All model attributes.
     * @return string|null The sanitized value with tags stripped.
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null)
        {
            return null;
        }

        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return strip_tags($decoded);
    }
}