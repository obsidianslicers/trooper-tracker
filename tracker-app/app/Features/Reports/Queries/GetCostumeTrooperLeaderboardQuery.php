<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Features\Concerns\HasLookback;
use App\Models\Costume;
use App\Models\Organization;
use Carbon\Carbon;

/**
 * @see GetCostumeTrooperLeaderboardQueryHandler
 */
readonly class GetCostumeTrooperLeaderboardQuery
{
    use HasLookback {
        parseLookback as private parseRequiredLookback;
    }

    public function __construct(
        public readonly Costume $costume,
        public readonly int|string|Carbon|null $lookback = null,
        public readonly ?Organization $organization = null,
        public readonly int $limit = 30,
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
