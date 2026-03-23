<?php

declare(strict_types=1);

namespace App\Services\Forums;

use App\Helpers\ForumBBCodeHelper;
use App\Models\Event;

class ForumThreadMessageService
{
    public function buildRosterSummary(Event $event): string
    {
        return ForumBBCodeHelper::rosterSummary($event);
    }

    public function buildThreadMessage(Event $event): string
    {
        $roster = $this->buildRosterSummary($event);

        return ForumBBCodeHelper::threadTemplate($event, $roster);
    }
}