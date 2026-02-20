<?php

declare(strict_types=1);

namespace App\Features\Notices\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Notice;

/**
 * Handler for retrieving all notices visible to a trooper.
 *
 * Returns notices that the trooper has permission to view based on:
 * - Organization membership (organization-specific notices)
 * - Global notices (visible to all troopers)
 * - Active status (only active notices are returned)
 *
 * Results are ordered by starts_at timestamp for chronological display.
 *
 * @implements QueryHandlerInterface<GetTrooperNoticesQuery>
 */
readonly class GetTrooperNoticesQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve visible notices.
     *
     * Uses the Notice::visibleTo() scope to filter notices based on:
     * - Trooper's organization memberships
     * - Global notices (organization_id is null)
     * - Active status (is_active = true)
     * - Unread only (unread_only = true filters to notices not yet read)
     *
     * Results are ordered by starts_at timestamp (ascending).
     *
     * @param  GetTrooperNoticesQuery  $message  The query containing the trooper
     * @return \Illuminate\Support\Collection<int, Notice> Collection of visible notices
     */
    public function __invoke(object $message): mixed
    {
        return Notice::visibleTo($message->trooper, true)
            ->orderBy(Notice::STARTS_AT)
            ->get();
    }
}
