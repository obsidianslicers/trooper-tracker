<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Events\Commands\SendEventDailyNotificationCommand;
use App\Features\Events\Queries\GetTroopersForDailyEventNotificationsQuery;
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
     * 1. Dispatching GetTroopersForDailyEventNotificationsQuery to retrieve troopers
     * 2. Dispatching SendEventDailyNotificationCommand for each trooper
     *
     * @param MagicBus $bus The message bus for dispatching queries and commands
     * @return void
     */
    public function handle(MagicBus $bus): void
    {
        $troopers = $bus->send(new GetTroopersForDailyEventNotificationsQuery());

        foreach ($troopers as $trooper)
        {
            $bus->send(new SendEventDailyNotificationCommand($trooper));
        }
    }
}
