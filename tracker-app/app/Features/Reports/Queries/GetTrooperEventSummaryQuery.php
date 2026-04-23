<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;

readonly class GetTrooperEventSummaryQuery
{
    public function __construct(
        public readonly Trooper $moderator,
        public readonly ?Carbon $date_start = null,
        public readonly ?Carbon $date_end = null,
        public readonly bool $active_only = false,
        public readonly int $page_size = 50,
        public readonly ?Organization $organization = null,
        public readonly string $sort = 'event_shifts_count',
        public readonly string $dir = 'desc',
    ) {}
}
