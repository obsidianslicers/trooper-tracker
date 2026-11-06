<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Troopers\Commands\Costumes\SendCostumeRequestNotifications;
use App\Models\Trooper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queues the officer notifications for a costume a trooper added themselves.
 */
class SendCostumeRequestNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int>  $trooper_costume_ids
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $trooper_costume_ids,
    ) {}

    public function handle(): void
    {
        SendCostumeRequestNotifications::call(
            trooper: $this->trooper,
            trooper_costume_ids: $this->trooper_costume_ids,
        );
    }
}
