<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

/**
 * Query to find EventTrooper rows whose credit data has been wiped.
 *
 * Returns ATTENDED EventTrooper rows that either show as "N/A" (no costume and not a
 * handler) or carry no org credit at all (no costume_organization_ids and no
 * organization_id) — the signature left behind by the admin roster-editor bug where
 * saving the roster form could silently reset another trooper's stored costume/credit
 * to a value no longer supported by their live membership/costume approvals.
 *
 * @see GetCorruptedEventTrooperCreditQueryHandler
 */
readonly class GetCorruptedEventTrooperCreditQuery
{
    /**
     * Create a new query instance.
     *
     * @param  int|null  $trooper_id  Restrict results to a single trooper, or null for all.
     */
    public function __construct(public readonly ?int $trooper_id = null) {}
}
