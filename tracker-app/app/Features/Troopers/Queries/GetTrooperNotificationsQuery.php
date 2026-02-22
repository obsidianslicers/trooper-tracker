<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Models\Trooper;

/**
 * Query to retrieve notification preferences for a trooper.
 *
 * Returns the organizational hierarchy with each organization marked
 * to indicate whether the trooper has notification preferences enabled
 * for that organization (should_notify flag on TrooperAssignment).
 *
 * @see GetTrooperNotificationsQueryHandler
 */
readonly class GetTrooperNotificationsQuery
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose notification preferences to retrieve
     */
    public function __construct(public readonly Trooper $trooper) {}
}
