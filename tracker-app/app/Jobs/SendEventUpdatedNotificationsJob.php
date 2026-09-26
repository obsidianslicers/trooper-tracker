<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventUpdatedNotificationCommand;
use App\Features\Events\Queries\GetTroopersForEventUpdatedQuery;
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

    public function handle(MagicBus $bus): void
    {
        $troopers = $bus->send(new GetTroopersForEventUpdatedQuery($this->event));

        foreach ($troopers as $trooper)
        {
            $bus->send(new SendEventUpdatedNotificationCommand($this->event, $trooper, $this->changed_fields));
        }
    }
}
