<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\ReconcileEventRosterCommand;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reconciles the event roster after capacity limits change.
 *
 * Thin orchestrator: all roster business logic lives in
 * ReconcileEventRosterCommandHandler.
 */
class ReconcileEventRosterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $changed_by,
    ) {}

    public function handle(MagicBus $bus): void
    {
        $bus->send(new ReconcileEventRosterCommand($this->event, $this->changed_by));
    }
}
