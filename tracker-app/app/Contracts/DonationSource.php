<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Trooper;
use App\Support\DonationSummary;

/**
 * Donations recorded somewhere else, such as forum subscriptions, that count toward
 * a trooper's donation months, total donated and milestones. Plugins register one
 * with addDonationSource(); a fresh instance is made for each run, so it may cache
 * what it fetches for that run.
 */
interface DonationSource
{
    /**
     * Used by the achievement recalculation, a chunk of troopers at a time.
     *
     * @param  array<int, int>  $trooper_ids
     * @return array<int, DonationSummary> Keyed by trooper ID; troopers with none may be left out.
     */
    public function summariesFor(array $trooper_ids): array;

    /**
     * Used for live totals on one trooper's service record.
     */
    public function summaryFor(Trooper $trooper): ?DonationSummary;

    /**
     * Everything donated this calendar month, for the support goal widget. Return
     * null when it couldn't be fetched, so a passing outage isn't cached.
     */
    public function monthTotal(): ?float;
}
