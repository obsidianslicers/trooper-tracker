<?php

declare(strict_types=1);

namespace App\Features\Reports\Concerns;

use Carbon\Carbon;

trait HasLookback
{
    /**
     * Convert the lookback parameter to a Carbon date if needed.
     */
    public function parseLookback(): Carbon
    {
        if (is_int($this->lookback))
        {
            return now()->subDays($this->lookback);
        }
        if (is_string($this->lookback))
        {
            return Carbon::parse($this->lookback);
        }

        return $this->lookback;
    }
}
