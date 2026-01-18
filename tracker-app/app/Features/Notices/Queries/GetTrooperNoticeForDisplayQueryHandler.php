<?php

declare(strict_types=1);

namespace App\Features\Notices\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Notice;

/**
 * Handler for retrieving notice display information for a trooper.
 *
 * Returns an array containing:
 * - count: The number of visible notices (filtered by unread_only flag)
 * - notice: A single Notice object if count equals 1, otherwise null
 *
 * This handler is optimized for UI display where showing a single notice
 * directly is preferred when only one notice is available.
 *
 * @implements QueryHandlerInterface<GetTrooperNoticeForDisplayQuery>
 */
readonly class GetTrooperNoticeForDisplayQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve notice display data.
     *
     * Counts visible notices and returns the single notice if only one exists.
     * Uses Notice::visibleTo() scope with the unread_only parameter.
     *
     * @param GetTrooperNoticeForDisplayQuery $message The query containing trooper and filter criteria
     * @return array{count: int, notice: ?Notice} Display data with count and optional single notice
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