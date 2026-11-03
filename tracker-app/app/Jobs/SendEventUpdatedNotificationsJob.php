<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Events\Commands\SendEventUpdatedNotification;
use App\Messages\Events\Queries\GetTroopersForEventUpdated;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEventUpdatedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly array $changed_fields,
    ) {}

    public function handle(): void
    {
        $troopers = GetTroopersForEventUpdated::call(event: $this->event);

        foreach ($troopers as $trooper)
        {
            SendEventUpdatedNotification::call(
                event: $this->event,
                trooper: $trooper,
                changed_fields: $this->changed_fields,
            );
        }
    }
}
