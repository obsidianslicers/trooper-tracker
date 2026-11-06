<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Events\Commands\ReconcileEventRoster;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reconciles the event roster after capacity limits change.
 *
 * Thin orchestrator: all roster business logic lives in
 * ReconcileEventRoster.
 */
class ReconcileEventRosterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $changed_by,
    ) {}

    public function handle(): void
    {
        ReconcileEventRoster::call(event: $this->event, changed_by: $this->changed_by);
    }
}
