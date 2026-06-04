<?php

declare(strict_types=1);

namespace App\Services\Forums;

use App\Helpers\ForumBBCodeHelper;
use App\Models\Event;

/**
 * Builds forum thread message content for event posts.
 */
class ForumThreadMessageService
{
    /**
     * Generates a formatted roster summary for the given event.
     */
    public function buildRosterSummary(Event $event): string
    {
        return ForumBBCodeHelper::rosterSummary($event);
    }

    /**
     * Builds the full forum thread message for the given event.
     */
    public function buildThreadMessage(Event $event): string
    {
        $roster = $this->buildRosterSummary($event);

        return ForumBBCodeHelper::threadTemplate($event, $roster);
    }
}
