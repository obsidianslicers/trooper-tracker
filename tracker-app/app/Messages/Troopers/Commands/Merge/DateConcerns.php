<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

trait DateConcerns
{
    private function earliestDateTime(mixed $target_value, mixed $source_value): mixed
    {
        if ($target_value === null)
        {
            return $source_value;
        }

        if ($source_value === null)
        {
            return $target_value;
        }

        return $source_value->lessThan($target_value) ? $source_value : $target_value;
    }

    private function latestDateTime(mixed $target_value, mixed $source_value): mixed
    {
        if ($target_value === null)
        {
            return $source_value;
        }

        if ($source_value === null)
        {
            return $target_value;
        }

        return $source_value->greaterThan($target_value) ? $source_value : $target_value;
    }
}
