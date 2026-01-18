<?php

declare(strict_types=1);

namespace App\Features\Notices\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve notice display information for a trooper.
 *
 * Returns the count of visible notices and optionally a single notice
 * when exactly one notice is available. Supports filtering to unread notices only.
 *
 * @see GetTrooperNoticeForDisplayQueryHandler
 */
readonly class GetTrooperNoticeForDisplayQuery
{
    /**
     * Create a new query instance.
     *
     * @param Trooper $trooper The trooper requesting notice display information
     * @param bool $unread_only Whether to count only unread notices (default: false)
     */
    public function __construct(
        public readonly Trooper $trooper,
        public readonly bool $unread_only = false
    ) {
    }
}