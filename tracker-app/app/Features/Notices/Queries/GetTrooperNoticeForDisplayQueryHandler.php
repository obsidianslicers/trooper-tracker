<?php

declare(strict_types=1);

namespace App\Features\Notices\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Notice;

/**
 * Handler for retrieving notices for display to a trooper.
 *
 * Processes GetTrooperNoticesQuery to return notices based on:
 * - Trooper visibility: Returns only notices visible to the trooper
 *
 * All results are ordered by the notice's created_at field for consistent UI display.
 *
 * @implements QueryHandlerInterface<GetTrooperNoticeForDisplayQuery>
 */
readonly class GetTrooperNoticeForDisplayQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve notices for display to a trooper.
     *
     * Query behavior:
     * 1. If unread_only is true: Returns only unread notices visible to the trooper
     * 2. Otherwise: Returns all notices visible to the trooper
     *
     * @param GetTrooperNoticeForDisplayQuery $message The query containing filter criteria
     * @return array{count: int, notice: ?Notice} The count of notices and the single notice if only one is available
     */
    public function __invoke(object $message): mixed
    {
        /** @var GetTrooperNoticeForDisplayQuery $message */

        $notice = null;

        $count = Notice::visibleTo($message->trooper, $message->unread_only)->count();

        if ($count == 1)
        {
            $notice = Notice::visibleTo($message->trooper, $message->unread_only)->first();
        }

        return compact('count', 'notice');
    }
}