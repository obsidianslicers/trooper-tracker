<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Models\Event;
use App\Features\Events\Queries\GetTroopersForEventCancelledQuery;
use App\Models\TrooperFriend;
use App\Notifications\Events\BroadcastEventMessageNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastEventMessageJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Event $event, public readonly string $message)
    {
    }

    public function handle(MagicBus $bus): void
    {
        //  not for a cancelled event, but gets a list of troopers "GOING"
        //  to the event
        $query = new GetTroopersForEventCancelledQuery($this->event);

        $troopers = $bus->send($query);

        foreach ($troopers as $trooper)
        {
            $trooper->notify(new BroadcastEventMessageNotification($this->event, $this->message));
        }
    }
}
