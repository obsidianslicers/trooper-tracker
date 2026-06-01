<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Concerns\HasLookback;
use Carbon\Carbon;

/**
 * @see GetCostumeArsenalQueryHandler
 */
readonly class GetCostumeArsenalQuery
{
    use HasLookback {
        parseLookback as private parseRequiredLookback;
    }

    public function __construct(
        public readonly int|string|Carbon|null $lookback = null,
    ) {}

    public function parseLookback(): ?Carbon
    {
        if ($this->lookback === null)
        {
            return null;
        }

        return $this->parseRequiredLookback();
    }
}
