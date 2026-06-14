<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Models\Trooper;
use Carbon\Carbon;

readonly class GetDonationEventSummaryQuery
{
    public function __construct(
        public readonly Trooper $moderator,
        public readonly ?Carbon $date_start = null,
        public readonly ?Carbon $date_end = null,
        public readonly bool $charity_only = false,
        public readonly int $page_size = 50,
        public readonly string $sort = 'event_start',
        public readonly string $dir = 'desc',
        public readonly array $selected_org_ids = [],
        public readonly array $accessible_org_ids = [],
    ) {}
}
