<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

/**
 * Query to retrieve a trooper's complete service record.
 *
 * Returns comprehensive service data including organizations, costumes,
 * service summary (shifts, hours, rank, funds, milestones), upcoming and
 * recent event shifts, donations, and awards received.
 *
 * @see GetTrooperServiceRecordQueryHandler
 */
final readonly class GetTrooperServiceRecordQuery
{
    /**
     * Create a new query instance.
     *
     * @param  int  $trooper_id  The ID of the trooper whose service record to retrieve
     */
    public function __construct(
        public int $trooper_id
    ) {}
}
