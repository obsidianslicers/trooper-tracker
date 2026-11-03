<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Events\Commands\SendEventDailyNotification;
use App\Messages\Events\Queries\GetTroopersForDailyEventNotifications;
use Illuminate\Console\Command;

/**
 * Artisan command to send daily event notification digests to troopers.
 *
 * This command orchestrates the process of identifying troopers who need
 * daily notification digests and sending consolidated emails containing
 * all pending event notifications.
 */
class SendDailyEventNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:send-daily-event-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily event notifications.';

    /**
     * Execute the console command.
     *
     * Orchestrates the daily notification process by:
     * 1. Dispatching GetTroopersForDailyEventNotifications to retrieve troopers
     * 2. Dispatching SendEventDailyNotification for each trooper
     */
    public function handle(): void
    {
        $troopers = GetTroopersForDailyEventNotifications::call();

        foreach ($troopers as $trooper)
        {
            SendEventDailyNotification::call(trooper: $trooper);
        }
    }
}
