<?php

declare(strict_types=1);

namespace App\Services\SystemChecks;

class IniValue
{
    /**
     * Convert a php.ini value such as "100M" or "1G" to a number.
     * Returns null for unlimited values ("-1", or "0" for time limits).
     */
    public static function toNumber(string $value): ?int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1')
        {
            return null;
        }

        $number = (int) $value;
        $multiplier = match (strtoupper(substr($value, -1)))
        {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        return $number * $multiplier;
    }
}
