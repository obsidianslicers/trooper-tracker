<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use Illuminate\Support\Collection;

/**
 * Command to store or update trooper achievements based on event statistics.
 *
 * This command accepts aggregated trooper event statistics and processes them
 * to determine achievement thresholds, ranks, and other metrics to be stored
 * in the trooper_achievements table.
 */
readonly class StoreTrooperAchievementsCommand
{
    /**
     * Create a new command instance.
     *
     * @param Collection $trooper_stats Collection of trooper event statistics
     */
    public function __construct(
        public Collection $trooper_stats
    ) {
    }
}
