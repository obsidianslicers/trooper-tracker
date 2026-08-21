<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PhoneNumberCast implements CastsAttributes
{
    /**
     * Format the phone number from DB (3213213144 -> 321-321-3144).
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value))
        {
            return null;
        }

        // Strip non-digits just in case raw data has extra formatting
        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) === 10)
        {
            return sprintf(
                '%s-%s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4)
            );
        }

        return $value;
    }

    /**
     * Sanitize the phone number for storage (321-321-3144 -> 3213213144).
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value))
        {
            return null;
        }

        // Keep only numeric characters for database storage
        return preg_replace('/\D/', '', $value);
    }
}