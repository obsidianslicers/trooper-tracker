<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

/**
 * Command to promote the next standby trooper when someone cancels.
 *
 * When a trooper cancels their event sign-up, this command finds the next
 * trooper on standby (STAND_BY status) for the same shift and promotes them
 * to GOING status, sending them a notification email.
 *
 * @see PromoteNextInLineEventTrooperCommandHandler
 */
readonly class PromoteNextInLineEventTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper who cancelled (used to find their shift).
     */
    public function __construct(
        public readonly EventTrooper $event_trooper,
        public readonly bool $global_was_full = false,
        public readonly ?int $effective_org_id = null,
        public readonly ?bool $override_is_handler = null,
    ) {}
}
