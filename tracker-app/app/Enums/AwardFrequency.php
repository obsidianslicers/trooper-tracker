<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\Carbon;

/**
 * Defines the frequency at which an award can be given.
 */
enum AwardFrequency: string
{
    use HasEnumHelpers;

    /**
     * The award is a one-time occurrence.
     */
    case ONCE = 'once';

    /**
     * The award is given out at random intervals.
     */
    case RANDOM = 'random';

    /**
     * The award is given out on a monthly basis.
     */
    case MONTHLY = 'monthly';

    /**
     * The award is given out on a quarterly basis.
     */
    case QUARTERLY = 'quarterly';

    /**
     * The award is given out on an annual basis.
     */
    case ANNUALLY = 'annually';
    public function normalizeDate(Carbon|string $date): Carbon
    {
        $date = is_string($date) ? Carbon::parse($date) : $date;

        return match ($this)
        {
            self::ONCE,
            self::RANDOM => $date->copy(),
            self::MONTHLY => $date->copy()->startOfMonth(),
            self::QUARTERLY => $date->copy()->firstOfQuarter(),
            self::ANNUALLY => $date->copy()->startOfYear(),
        };
    }
}