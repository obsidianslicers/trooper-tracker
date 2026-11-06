<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Events\Commands\SendTentativeReminder;
use App\Messages\Events\Queries\GetTentativeEventTroopers;
use Illuminate\Console\Command;

class SendTentativeReminders extends Command
{
    protected $signature = 'tracker:send-tentative-reminders';

    protected $description = 'Send daily reminders to troopers with tentative event status.';

    public function handle(): void
    {
        $event_troopers = GetTentativeEventTroopers::call();

        foreach ($event_troopers as $event_trooper)
        {
            SendTentativeReminder::call(event_trooper: $event_trooper);
        }
    }
}
