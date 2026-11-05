<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Events\Queries\GetTroopersForEventCancelled;
use App\Models\Event;
use App\Notifications\Events\BroadcastEventMessageNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastEventMessageJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Event $event, public readonly string $message) {}

    public function handle(): void
    {
        //  not for a cancelled event, but gets a list of troopers "GOING"
        //  to the event
        $troopers = GetTroopersForEventCancelled::call(event: $this->event);

        foreach ($troopers as $trooper)
        {
            $trooper->notify(new BroadcastEventMessageNotification($this->event, $this->message));
        }
    }
}
