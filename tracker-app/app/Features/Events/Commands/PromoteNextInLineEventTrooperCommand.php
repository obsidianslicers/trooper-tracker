<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventTrooper;

/**
 * Command to promote the next event trooper in line.
 *
 * Updates the trooper model with validated data and optionally marks
 * the trooper's initial setup as completed by setting setup_completed_at.
 *
 * @see PromoteNextInLineEventTrooperCommandHandler
 */
readonly class PromoteNextInLineEventTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param EventTrooper $event_trooper The event trooper that cancelled
     */
    public function __construct(public readonly EventTrooper $event_trooper)
    {
    }
}

