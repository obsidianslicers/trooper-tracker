<?php

declare(strict_types=1);

namespace App\Messages\Concerns;

/**
 * Report numbers as the reports have always shown them: in the reader's locale, with a dash
 * for zero.
 */
trait FormatsReportNumbers
{
    protected function count(int|float|string|null $value): string
    {
        return (float) $value == 0.0 ? '-' : format_number($value);
    }

    /**
     * Money with cents only when the amount has them.
     */
    protected function funds(int|float|string|null $value): string
    {
        return (float) $value == 0.0 ? '-' : format_funds($value);
    }
}
