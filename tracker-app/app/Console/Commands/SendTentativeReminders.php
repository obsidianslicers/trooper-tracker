<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendTentativeReminderCommand;
use App\Features\Events\Queries\GetTentativeEventTroopersQuery;
use Illuminate\Console\Command;

class SendTentativeReminders extends Command
{
    protected $signature = 'tracker:send-tentative-reminders';

    protected $description = 'Send daily reminders to troopers with tentative event status.';

    public function handle(MagicBus $bus): void
    {
        $event_troopers = $bus->send(new GetTentativeEventTroopersQuery);

        foreach ($event_troopers as $event_trooper)
        {
            $bus->send(new SendTentativeReminderCommand($event_trooper));
        }
    }
}
