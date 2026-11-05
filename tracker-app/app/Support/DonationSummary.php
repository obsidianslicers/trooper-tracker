<?php

declare(strict_types=1);

namespace App\Support;

/**
 * One trooper's donations from a source outside TroopTracker's own records.
 */
final readonly class DonationSummary
{
    /**
     * @param  array<string, true>  $month_keys  Each month ("Y-m") with a donation.
     */
    public function __construct(
        public array $month_keys = [],
        public float $total = 0.0,
        public bool $is_active = false,
    ) {}
}
