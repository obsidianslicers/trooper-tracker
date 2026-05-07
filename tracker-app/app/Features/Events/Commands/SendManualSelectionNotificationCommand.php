<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;
use App\Models\Trooper;

/**
 * Command to send a manual selection notification email to an event trooper.
 *
 * Notifies the trooper that they were approved (GOING) or moved back to stand-by
 * during a manual selection event.
 *
 * @see SendManualSelectionNotificationCommandHandler
 */
readonly class SendManualSelectionNotificationCommand
{
    /**
     * Create a new command instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper being notified.
     * @param  Trooper  $actioned_by  The moderator who performed the status change.
     * @param  bool  $is_approved  True if approved to GOING, false if moved to STAND_BY.
     */
    public function __construct(
        public readonly EventTrooper $event_trooper,
        public readonly Trooper $actioned_by,
        public readonly bool $is_approved,
    ) {}
}
