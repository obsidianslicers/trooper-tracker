<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to find ATTENDED shifts with no visible "Credited To" organization.
 *
 * "Missing credit" is defined by what the service-record page actually renders
 * (see HasOrgCreditAnnotation), not merely whether the underlying DB columns are
 * null — a row can have a stored organization_id/costume_organization_ids that no
 * longer resolves to any org in the trooper's current organizations() list.
 *
 * @see GetEventTroopersMissingCreditQueryHandler
 */
final readonly class GetEventTroopersMissingCreditQuery
{
    /**
     * @param  Trooper  $actor  The moderator/administrator running the search.
     * @param  int|null  $trooper_id  Restrict results to a single trooper, when given.
     */
    public function __construct(
        public Trooper $actor,
        public ?int $trooper_id = null,
    ) {}
}
