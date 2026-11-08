<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Events\Commands\Verification\SendTroopVerificationNotifications;
use App\Models\EventTrooper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queues the officer notifications for a troop a flagged trooper confirmed.
 */
class SendTroopVerificationNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function handle(): void
    {
        SendTroopVerificationNotifications::call(event_trooper: $this->event_trooper);
    }
}
