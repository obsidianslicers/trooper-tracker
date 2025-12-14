<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SanitizeHtmlCast implements CastsAttributes
{
    /**
     * Decode HTML entities and strip tags on get.
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
     * Pass-through on set (optional: sanitize on write if desired).
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