<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Models\Event;
use App\Models\Trooper;

/**
 * What every part of the event page's roster needs to know: the event, who is looking, and
 * whether they moderate it.
 */
final class EventDisplayContext
{
    public readonly bool $can_moderate;

    public readonly bool $can_update;

    public readonly bool $has_brief_ack;

    public function __construct(
        public readonly Event $event,
        public readonly Trooper $viewer,
    ) {
        $this->can_moderate = $viewer->isModeratorForOrganization($event->organization);
        $this->can_update = $viewer->can('update', $event);
        $this->has_brief_ack = !$event->require_mission_brief_ack
            || $event->hasMissionBriefAcknowledgementFor($viewer);
    }
}
