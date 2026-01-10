<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use App\Models\Trooper;
use App\Services\Events\SendEventDailyNotificationCommand;
use App\Services\Troopers\GetTroopersForDailyEventNotificationsQuery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
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
     * @return void
     */
    public function handle(
        GetTroopersForDailyEventNotificationsQuery $get_troopers,
        SendEventDailyNotificationCommand $send_email): void
    {
        $troopers = $get_troopers();

        foreach ($troopers as $trooper)
        {
            $send_email($trooper);
        }
    }
}
